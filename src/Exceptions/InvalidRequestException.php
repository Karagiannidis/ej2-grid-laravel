<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Exceptions;

use RuntimeException;

final class InvalidRequestException extends RuntimeException implements Ej2GridException
{
    public static function unexpectedType(string $path, string $expected, mixed $actual): self
    {
        return new self(sprintf("Expected %s at '%s', got %s.", $expected, $path, get_debug_type($actual)));
    }

    public static function belowMinimum(string $path, int $minimum, int $actual): self
    {
        return new self(sprintf("Expected '%s' to be at least %d, got %d.", $path, $minimum, $actual));
    }

    public static function unknownOperator(string $path, string $operator): self
    {
        return new self(sprintf("Unknown filter operator '%s' at '%s'.", $operator, $path));
    }

    public static function unknownSortDirection(string $path, string $direction): self
    {
        return new self(sprintf("Unknown sort direction '%s' at '%s'. Expected 'ascending' or 'descending'.", $direction, $path));
    }

    public static function unknownCondition(string $path, string $condition): self
    {
        return new self(sprintf("Unknown filter condition '%s' at '%s'. Expected 'and' or 'or'.", $condition, $path));
    }
}
