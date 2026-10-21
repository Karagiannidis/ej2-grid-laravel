<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Karagiannidis\Ej2Grid\DTOs\FilterGroup;
use Karagiannidis\Ej2Grid\DTOs\FilterPredicate;
use Karagiannidis\Ej2Grid\Enums\Operator;
use Karagiannidis\Ej2Grid\Exceptions\InvalidRequestException;
use Karagiannidis\Ej2Grid\Fields\ColumnField;

/**
 * @internal
 */
final readonly class GridFilter
{
    /**
     * @param  array<string, Field>  $fields
     */
    public function __construct(private array $fields) {}

    /**
     * @param  Builder<Model>  $query
     */
    public function apply(Builder $query, FilterGroup $filters): void
    {
        $this->group($query, $filters, 'where');
    }

    /**
     * Each predicate goes in its own brackets
     *
     * @param  Builder<Model>  $query
     */
    private function group(Builder $query, FilterGroup $group, string $path): void
    {
        foreach ($group->predicates as $index => $predicate) {
            $predicatePath = sprintf('%s.%d', $path, $index);

            $query->where(function (Builder $nested) use ($predicate, $predicatePath): void {
                if ($predicate instanceof FilterGroup) {
                    $this->group($nested, $predicate, $predicatePath.'.predicates');
                } else {
                    $this->predicate($nested, $predicate, $predicatePath);
                }
            }, boolean: $group->condition->value);
        }
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function predicate(Builder $query, FilterPredicate $predicate, string $path): void
    {
        $field = $this->fields[$predicate->field]
            ?? throw InvalidRequestException::unknownField($path.'.field', $predicate->field);

        if (! $field instanceof ColumnField || ! $field->isFilterable()) {
            throw InvalidRequestException::notFilterable($path.'.field', $predicate->field);
        }

        $column = $query->qualifyColumn($field->column);

        match ($predicate->operator) {
            Operator::Equal => $this->equal($query, $column, $predicate->value),
            Operator::NotEqual => $this->notEqual($query, $column, $predicate->value),
            Operator::GreaterThan => $this->compare($query, $column, '>', $predicate->value),
            Operator::GreaterThanOrEqual => $this->compare($query, $column, '>=', $predicate->value),
            Operator::LessThan => $this->compare($query, $column, '<', $predicate->value),
            Operator::LessThanOrEqual => $this->compare($query, $column, '<=', $predicate->value),
            default => throw InvalidRequestException::unsupportedOperator($path.'.operator', $predicate->operator->value),
        };
    }

    /**
     * EJ2 sends a null value when the user ticks "Blanks".
     *
     * @param  Builder<Model>  $query
     */
    private function equal(Builder $query, string $column, mixed $value): void
    {
        if ($value === null) {
            $query->whereNull($column);

            return;
        }

        $query->where($column, '=', $value);
    }

    /**
     * EJ2 counts an empty cell as not equal to any value.
     *
     * @param  Builder<Model>  $query
     */
    private function notEqual(Builder $query, string $column, mixed $value): void
    {
        if ($value === null) {
            $query->whereNotNull($column);

            return;
        }

        $query->where($column, '!=', $value)->orWhereNull($column);
    }

    /**
     * EJ2 sends a null value for a range bound the user left empty.
     *
     * @param  Builder<Model>  $query
     * @param  '>'|'>='|'<'|'<='  $operator
     */
    private function compare(Builder $query, string $column, string $operator, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        $query->where($column, $operator, $value);
    }
}
