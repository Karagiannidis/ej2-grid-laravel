<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Exceptions;

use LogicException;

final class InvalidGridException extends LogicException implements Ej2GridException
{
    public static function modelNotFound(string $grid, string $model): self
    {
        return new self(sprintf("%s::model() must return an Eloquent model class, got '%s'.", $grid, $model));
    }

    public static function emptyFields(string $grid): self
    {
        return new self(sprintf('%s::fields() must return at least one field.', $grid));
    }

    public static function duplicateField(string $grid, string $name): self
    {
        return new self(sprintf("%s::fields() defines the field '%s' more than once.", $grid, $name));
    }
}
