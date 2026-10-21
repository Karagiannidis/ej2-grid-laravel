<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Karagiannidis\Ej2Grid\DTOs\GridRequest;
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

        $query = $grid->buildQuery($request);
        $fields = $grid->fieldMap();

        $this->filter($query, $request, $fields);
        $this->sort($query, $request, $fields);
        $this->orderByKey($query);
        $this->paginate($query, $request);

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
    private function sort(Builder $query, GridRequest $request, array $fields): void
    {
        foreach ($request->sorts as $index => $sort) {
            $path = sprintf('sorted.%d.name', $index);
            $field = $fields[$sort->field] ?? throw InvalidRequestException::unknownField($path, $sort->field);

            if (! $field instanceof ColumnField || ! $field->isSortable()) {
                throw InvalidRequestException::notSortable($path, $sort->field);
            }

            $query->orderBy($query->qualifyColumn($field->column), $sort->direction->toSql());
        }
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
