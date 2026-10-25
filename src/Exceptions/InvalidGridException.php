<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Exceptions;

use LogicException;

final class InvalidGridException extends LogicException implements Ej2GridException
{
    public static function unsupportedRelation(string $field, string $relation, string $type): self
    {
        return new self(sprintf("Field '%s' reads relation '%s', which returns %s. Only a BelongsTo or HasOne holds one value per row.", $field, $relation, $type));
    }
}
