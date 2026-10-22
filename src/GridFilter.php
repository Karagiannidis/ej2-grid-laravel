<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Karagiannidis\Ej2Grid\DTOs\FilterGroup;
use Karagiannidis\Ej2Grid\DTOs\FilterPredicate;
use Karagiannidis\Ej2Grid\DTOs\Search;
use Karagiannidis\Ej2Grid\Enums\Operator;
use Karagiannidis\Ej2Grid\Exceptions\InvalidRequestException;
use Karagiannidis\Ej2Grid\Fields\ColumnField;
use Karagiannidis\Ej2Grid\Fields\TextField;
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
     * @param  Builder<Model>  $query
     * @param  list<Search>  $searches
     */
    public function search(Builder $query, array $searches): void
    {
        foreach ($searches as $index => $search) {
            $path = sprintf('search.%d', $index);

            $query->where(function (Builder $nested) use ($search, $path): void {
                foreach ($search->fields as $fieldIndex => $name) {
                    $field = $this->fields[$name]
                        ?? throw InvalidRequestException::unknownField(sprintf('%s.fields.%d', $path, $fieldIndex), $name);

                    if ($field instanceof ColumnField && $field->isSearchable()) {
                        $nested->orWhere(function (Builder $one) use ($field, $search, $path): void {
                            $this->condition($one, $field, $search->operator, $search->key, $search->ignoreCase, $path.'.key');
                        });
                    }
                }
            });
        }
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

        $this->condition($query, $field, $predicate->operator, $predicate->value, $predicate->ignoreCase, $path.'.value');
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function condition(Builder $query, ColumnField $field, Operator $operator, mixed $value, bool $ignoreCase, string $path): void
    {
        $column = $field->qualifiedColumn($query->getModel());
        $value = $field->filterValue($value, $path);
        $ignoreCase = $ignoreCase && $field instanceof TextField;
        $like = fn (Closure $pattern, bool $not = false) => $this->like($query, $column, $value, $path, $pattern, $not, $ignoreCase);

        match ($operator) {
            Operator::Equal => $ignoreCase && $value !== null ? $like($this->escape(...)) : $this->equal($query, $column, $value),
            Operator::NotEqual => $ignoreCase && $value !== null ? $like($this->escape(...), not: true) : $this->notEqual($query, $column, $value),
            Operator::GreaterThan => $this->compare($query, $column, '>', $value),
            Operator::GreaterThanOrEqual => $this->compare($query, $column, '>=', $value),
            Operator::LessThan => $this->compare($query, $column, '<', $value),
            Operator::LessThanOrEqual => $this->compare($query, $column, '<=', $value),
            Operator::Contains => $like(fn (string $text): string => '%'.$this->escape($text).'%'),
            Operator::DoesNotContain => $like(fn (string $text): string => '%'.$this->escape($text).'%', not: true),
            Operator::StartsWith => $like(fn (string $text): string => $this->escape($text).'%'),
            Operator::DoesNotStartWith => $like(fn (string $text): string => $this->escape($text).'%', not: true),
            Operator::EndsWith => $like(fn (string $text): string => '%'.$this->escape($text)),
            Operator::DoesNotEndWith => $like(fn (string $text): string => '%'.$this->escape($text), not: true),
            Operator::WildCard => $like(fn (string $text): string => '%'.strtr($this->escape($text), ['*' => '%', '?' => '_']).'%'),
            Operator::Like => $like(fn (string $text): string => str_replace('!', '!!', $text)),
            Operator::In => $this->in($query, $column, $value, $path),
            Operator::NotIn => $this->in($query, $column, $value, $path, not: true),
            Operator::IsNull => $query->whereNull($column),
            Operator::IsNotNull => $query->whereNotNull($column),
            Operator::IsEmpty => $query->where($column, '=', '')->orWhereNull($column),
            Operator::IsNotEmpty => $query->where($column, '!=', '')->whereNotNull($column),
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
     * @param  Builder<Model>  $query
     */
    private function in(Builder $query, string $column, mixed $value, string $path, bool $not = false): void
    {
        if (! is_array($value)) {
            throw InvalidRequestException::unexpectedType($path, 'a list', $value);
        }

        if (! $not) {
            $query->whereIn($column, $value);

            return;
        }

        // EJ2 counts an empty cell as not in any list, SQL's NOT IN leaves it out.
        $query->whereNotIn($column, $value)->orWhereNull($column);
    }

    /**
     * EJ2 sends a null value when the user clears the text, so nothing is filtered.
     *
     * @param  Builder<Model>  $query
     * @param  Closure(string): string  $pattern
     */
    private function like(Builder $query, string $column, mixed $value, string $path, Closure $pattern, bool $not, bool $ignoreCase): void
    {
        if ($value === null) {
            return;
        }

        if (! is_scalar($value)) {
            throw InvalidRequestException::unexpectedType($path, 'text', $value);
        }

        $query->whereRaw(new LikeExpression($column, $not, $ignoreCase), [$pattern((string) $value)]);

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
