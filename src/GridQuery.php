<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Karagiannidis\Ej2Grid\DTOs\GridRequest;
use Karagiannidis\Ej2Grid\Exceptions\InvalidGridException;
use Karagiannidis\Ej2Grid\Exceptions\InvalidRequestException;
use Karagiannidis\Ej2Grid\Fields\ColumnField;

/**
 * Applies what an EJ2 request asks for to a grid's base query.
 *
 * @internal
 */
final class GridQuery
{
    /**
     * @return Builder<Model>
     */
    public function build(Grid $grid, GridRequest $request): Builder
    {
        $this->ensureWithinPageLimit($grid, $request);

        $query = $this->buildUnpaged($grid, $request);

        $this->paginate($query, $request);

        return $query;
    }

    /**
     * @return Builder<Model>
     */
    public function buildUnpaged(Grid $grid, GridRequest $request): Builder
    {
        $query = $grid->buildQuery($request);
        $fields = $grid->fieldMap();

        $this->ensureGroupable($request, $fields);
        $this->join($query, $fields);
        $this->filter($query, $request, $fields);
        $this->search($query, $request, $fields);

        if ($request->distinct !== null) {
            $this->distinct($query, $request->distinct, $fields);
        } else {
            $this->sort($query, $request, $fields);
            $this->orderByKey($query);
        }

        return $query;
    }

    private function ensureWithinPageLimit(Grid $grid, GridRequest $request): void
    {
        $limit = $grid->pageSizeLimit();

        if ($limit === null) {
            return;
        }

        if ($request->take === null) {
            throw InvalidRequestException::missingTake($limit);
        }

        if ($request->take > $limit) {
            throw InvalidRequestException::pageSizeExceeded($request->take, $limit);
        }
    }

    /**
     * @param  array<string, Field>  $fields
     */
    private function ensureGroupable(GridRequest $request, array $fields): void
    {
        foreach ($request->groups as $index => $name) {
            $path = sprintf('group.%d', $index);
            $field = $fields[$name] ?? throw InvalidRequestException::unknownField($path, $name);

            if (! $field instanceof ColumnField || ! $field->isGroupable()) {
                throw InvalidRequestException::notGroupable($path, $name);
            }
        }
    }

    /**
     * Left joins each relation the fields read, once, and selects their values
     * next to the grid's own columns. A row without the related model gets
     * empty values, which filters treat like any empty cell.
     *
     * @param  Builder<Model>  $query
     * @param  array<string, Field>  $fields
     */
    private function join(Builder $query, array $fields): void
    {
        $related = $this->relatedFields($fields);

        if ($related === []) {
            return;
        }

        $model = $query->getModel();
        $joined = [];

        if ($query->getQuery()->columns === null) {
            $query->select($model->qualifyColumn('*'));
        }

        foreach ($related as $field) {
            $this->joinRelations($query, $field, $joined);

            $query->addSelect($field->qualifiedColumn($model).' as '.$field->attribute());
        }
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, true>  $joined
     */
    private function joinRelations(Builder $query, ColumnField $field, array &$joined): void
    {
        $model = $query->getModel();
        $parentAlias = $model->getTable();

        foreach ($field->relationAliases() as $alias => $name) {
            $relation = $this->relation($model, $name, $field);

            if (! isset($joined[$alias])) {
                $this->joinRelation($query, $relation, $parentAlias, $alias);
                $joined[$alias] = true;
            }

            $model = $relation->getRelated();
            $parentAlias = $alias;
        }
    }

    /**
     * @param  array<string, Field>  $fields
     * @return list<ColumnField>
     */
    private function relatedFields(array $fields): array
    {
        $related = [];

        foreach ($fields as $field) {
            if ($field instanceof ColumnField && $field->relation !== null) {
                $related[] = $field;
            }
        }

        return $related;
    }

    /**
     * @return BelongsTo<Model, Model>|HasOne<Model, Model>
     */
    private function relation(Model $model, string $name, ColumnField $field): BelongsTo|HasOne
    {
        $relation = $model->{$name}();

        if ($relation instanceof BelongsTo || $relation instanceof HasOne) {
            return $relation;
        }

        throw InvalidGridException::unsupportedRelation($field->name, $name, get_debug_type($relation));
    }

    /**
     * @param  Builder<Model>  $query
     * @param  BelongsTo<Model, Model>|HasOne<Model, Model>  $relation
     */
    private function joinRelation(Builder $query, BelongsTo|HasOne $relation, string $parentAlias, string $alias): void
    {
        [$parentColumn, $relatedColumn] = $relation instanceof BelongsTo
            ? [$relation->getForeignKeyName(), $relation->getOwnerKeyName()]
            : [$relation->getLocalKeyName(), $relation->getForeignKeyName()];

        $query->leftJoin(
            $relation->getRelated()->getTable().' as '.$alias,
            $parentAlias.'.'.$parentColumn,
            '=',
            $alias.'.'.$relatedColumn,
        );
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, Field>  $fields
     */
    private function filter(Builder $query, GridRequest $request, array $fields): void
    {
        new GridFilter($fields)->apply($query, $request->filters);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, Field>  $fields
     */
    private function search(Builder $query, GridRequest $request, array $fields): void
    {
        new GridFilter($fields)->search($query, $request->searches);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, Field>  $fields
     */
    private function sort(Builder $query, GridRequest $request, array $fields): void
    {
        // EJ2 lists the main sort last, the order its own sorting in the browser
        // applies them in. Grouped columns come after the rest, so they lead.
        foreach (array_reverse($request->sorts, preserve_keys: true) as $index => $sort) {
            $path = sprintf('sorted.%d.name', $index);
            $field = $fields[$sort->field] ?? throw InvalidRequestException::unknownField($path, $sort->field);

            if (! $field instanceof ColumnField || ! $field->isSortable()) {
                throw InvalidRequestException::notSortable($path, $sort->field);
            }

            $query->orderBy($field->qualifiedColumn($query->getModel()), $sort->direction->toSql());
        }
    }

    /**
     * One row per value of the field, in order, for a filter dropdown.
     *
     * @param  Builder<Model>  $query
     * @param  array<string, Field>  $fields
     */
    private function distinct(Builder $query, string $name, array $fields): void
    {
        $field = $fields[$name] ?? throw InvalidRequestException::unknownField('distinct', $name);

        if (! $field instanceof ColumnField || ! $field->isFilterable()) {
            throw InvalidRequestException::notFilterable('distinct', $name);
        }

        $column = $field->qualifiedColumn($query->getModel());

        // grouBy instead of distinct so count includes empty cells
        $query->select($column.' as '.$field->attribute())->groupBy($column)->orderBy($column);
    }

    /**
     * Goes last and breaks ties left by any other ordering.
     *
     * @param  Builder<Model>  $query
     */
    private function orderByKey(Builder $query): void
    {
        $query->orderBy($query->getModel()->getQualifiedKeyName());
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function paginate(Builder $query, GridRequest $request): void
    {
        if ($request->take === null) {
            return;
        }

        $query->skip($request->skip)->take($request->take);
    }
}
