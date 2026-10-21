<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Query;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;

/**
 * A LIKE condition on one column, with ! as the escape character.
 *
 * @internal
 */
final readonly class LikeExpression implements Expression
{
    public function __construct(
        private string $column,
        private bool $not = false,
    ) {}

    public function getValue(Grammar $grammar): string
    {
        return sprintf("%s %s ? escape '!'", $grammar->wrap($this->column), $this->not ? 'not like' : 'like');
    }
}
