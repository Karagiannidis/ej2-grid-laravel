<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Karagiannidis\Ej2Grid\DTOs\FilterGroup;
use Karagiannidis\Ej2Grid\DTOs\FilterPredicate;
use Karagiannidis\Ej2Grid\Enums\Operator;
use Karagiannidis\Ej2Grid\Exceptions\InvalidRequestException;
use Karagiannidis\Ej2Grid\Fields\ColumnField;
use Karagiannidis\Ej2Grid\Query\LikeExpression;

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
            Operator::Contains => $this->like($query, $column, $predicate->value, $path, fn (string $text): string => '%'.$this->escape($text).'%'),
            Operator::DoesNotContain => $this->like($query, $column, $predicate->value, $path, fn (string $text): string => '%'.$this->escape($text).'%', not: true),
            Operator::StartsWith => $this->like($query, $column, $predicate->value, $path, fn (string $text): string => $this->escape($text).'%'),
            Operator::DoesNotStartWith => $this->like($query, $column, $predicate->value, $path, fn (string $text): string => $this->escape($text).'%', not: true),
            Operator::EndsWith => $this->like($query, $column, $predicate->value, $path, fn (string $text): string => '%'.$this->escape($text)),
            Operator::DoesNotEndWith => $this->like($query, $column, $predicate->value, $path, fn (string $text): string => '%'.$this->escape($text), not: true),
            Operator::WildCard => $this->like($query, $column, $predicate->value, $path, fn (string $text): string => '%'.strtr($this->escape($text), ['*' => '%', '?' => '_']).'%'),
            Operator::Like => $this->like($query, $column, $predicate->value, $path, fn (string $text): string => str_replace('!', '!!', $text)),
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

    /**
     * EJ2 sends a null value when the user clears the text, so nothing is filtered.
     *
     * @param  Builder<Model>  $query
     * @param  Closure(string): string  $pattern
     */
    private function like(Builder $query, string $column, mixed $value, string $path, Closure $pattern, bool $not = false): void
    {
        if ($value === null) {
            return;
        }

        if (! is_scalar($value)) {
            throw InvalidRequestException::unexpectedType($path.'.value', 'text', $value);
        }

        $query->whereRaw(new LikeExpression($column, $not), [$pattern((string) $value)]);

        // EJ2 counts an empty cell as not containing the text, SQL's NOT LIKE leaves it out.
        if ($not) {
            $query->orWhereNull($column);
        }
    }

    /**
     * Makes % and _ in the user's text match themselves. The escape character is !
     * rather than \, which MySQL would need doubled.
     */
    private function escape(string $text): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text);
    }
}
