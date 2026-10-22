<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Http;

use Karagiannidis\Ej2Grid\DTOs\FilterGroup;
use Karagiannidis\Ej2Grid\DTOs\FilterPredicate;
use Karagiannidis\Ej2Grid\DTOs\GridRequest;
use Karagiannidis\Ej2Grid\DTOs\Search;
use Karagiannidis\Ej2Grid\DTOs\Sort;
use Karagiannidis\Ej2Grid\Enums\Condition;
use Karagiannidis\Ej2Grid\Enums\Operator;
use Karagiannidis\Ej2Grid\Enums\SortDirection;
use Karagiannidis\Ej2Grid\Exceptions\InvalidRequestException;

final class GridRequestParser
{
    private const int MAX_GROUP_DEPTH = 10;

    /**
     * @param  array<mixed>  $payload
     */
    public function parse(array $payload): GridRequest
    {
        return new GridRequest(
            skip: isset($payload['skip']) ? $this->int($payload, 'skip', 0) : 0,
            take: isset($payload['take']) ? $this->int($payload, 'take', 1) : null,
            requiresCounts: $this->bool($payload, 'requiresCounts'),
            filters: new FilterGroup(Condition::And, $this->predicates($payload['where'] ?? [], 'where', 0)),
            sorts: $this->sorts($payload),
            searches: $this->searches($payload),
            groups: $this->strings($payload, 'group'),
            select: $this->strings($payload, 'select'),
        );
    }

    /**
     * @return list<FilterPredicate|FilterGroup>
     */
    private function predicates(mixed $raw, string $path, int $depth): array
    {
        if (! is_array($raw)) {
            throw InvalidRequestException::unexpectedType($path, 'a list', $raw);
        }

        $predicates = [];

        foreach (array_values($raw) as $index => $entry) {
            $predicates[] = $this->predicate($entry, sprintf('%s.%d', $path, $index), $depth);
        }

        return $predicates;
    }

    /**
     * @param  int  $depth
     */
    private function predicate(mixed $raw, string $path, int $depth): FilterPredicate|FilterGroup
    {
        if (! is_array($raw)) {
            throw InvalidRequestException::unexpectedType($path, 'a predicate', $raw);
        }

        if ($this->bool($raw, 'isComplex', $path)) {
            if ($depth === self::MAX_GROUP_DEPTH) {
                throw InvalidRequestException::filterTooDeep($path, self::MAX_GROUP_DEPTH);
            }

            return new FilterGroup(
                condition: $this->condition($raw, $path),
                predicates: $this->predicates($raw['predicates'] ?? null, $path.'.predicates', $depth + 1),
            );
        }

        return new FilterPredicate(
            field: $this->string($raw, 'field', $path),
            operator: $this->operator($raw, $path),
            value: $raw['value'] ?? null,
            ignoreCase: $this->bool($raw, 'ignoreCase', $path),
        );
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<Sort>
     */
    private function sorts(array $payload): array
    {
        $sorts = [];

        foreach ($this->list($payload, 'sorted') as $index => $raw) {
            $sorts[] = $this->sort($raw, 'sorted.'.$index);
        }

        return $sorts;
    }

    private function sort(mixed $raw, string $path): Sort
    {
        if (! is_array($raw)) {
            throw InvalidRequestException::unexpectedType($path, 'a sort', $raw);
        }

        $direction = $this->string($raw, 'direction', $path);

        return new Sort(
            field: $this->string($raw, 'name', $path),
            direction: SortDirection::tryFrom($direction)
                ?? throw InvalidRequestException::unknownSortDirection($path.'.direction', $direction),
        );
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<Search>
     */
    private function searches(array $payload): array
    {
        $searches = [];

        foreach ($this->list($payload, 'search') as $index => $raw) {
            $searches[] = $this->search($raw, 'search.'.$index);
        }

        return $searches;
    }

    private function search(mixed $raw, string $path): Search
    {
        if (! is_array($raw)) {
            throw InvalidRequestException::unexpectedType($path, 'a search', $raw);
        }

        return new Search(
            key: $this->string($raw, 'key', $path),
            fields: $this->strings($raw, 'fields', $path),
            operator: $this->operator($raw, $path),
            ignoreCase: $this->bool($raw, 'ignoreCase', $path),
        );
    }

    /**
     * @param  array<mixed>  $raw
     */
    private function operator(array $raw, string $path): Operator
    {
        $operator = $this->string($raw, 'operator', $path);

        return Operator::tryFrom($operator)
            ?? throw InvalidRequestException::unknownOperator($path.'.operator', $operator);
    }

    /**
     * @param  array<mixed>  $raw
     */
    private function condition(array $raw, string $path): Condition
    {
        $condition = $this->string($raw, 'condition', $path);

        return Condition::tryFrom($condition)
            ?? throw InvalidRequestException::unknownCondition($path.'.condition', $condition);
    }

    /**
     * @param  array<mixed>  $raw
     */
    private function int(array $raw, string $key, int $minimum): int
    {
        $value = $raw[$key];

        if (! is_int($value)) {
            throw InvalidRequestException::unexpectedType($key, 'an integer', $value);
        }

        if ($value < $minimum) {
            throw InvalidRequestException::belowMinimum($key, $minimum, $value);
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $raw
     */
    private function bool(array $raw, string $key, string $path = ''): bool
    {
        $value = $raw[$key] ?? false;

        if (! is_bool($value)) {
            throw InvalidRequestException::unexpectedType($this->join($path, $key), 'a boolean', $value);
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $raw
     */
    private function string(array $raw, string $key, string $path): string
    {
        $value = $raw[$key] ?? null;

        if (! is_string($value)) {
            throw InvalidRequestException::unexpectedType($this->join($path, $key), 'a string', $value);
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $raw
     * @return list<string>
     */
    private function strings(array $raw, string $key, string $path = ''): array
    {
        $strings = [];

        foreach ($this->list($raw, $key, $path) as $index => $value) {
            if (! is_string($value)) {
                throw InvalidRequestException::unexpectedType($this->join($path, sprintf('%s.%d', $key, $index)), 'a string', $value);
            }

            $strings[] = $value;
        }

        return $strings;
    }

    /**
     * @param  array<mixed>  $raw
     * @return list<mixed>
     */
    private function list(array $raw, string $key, string $path = ''): array
    {
        $value = $raw[$key] ?? [];

        if (! is_array($value)) {
            throw InvalidRequestException::unexpectedType($this->join($path, $key), 'a list', $value);
        }

        return array_values($value);
    }

    private function join(string $path, string $key): string
    {
        return $path === '' ? $key : sprintf('%s.%s', $path, $key);
    }
}
