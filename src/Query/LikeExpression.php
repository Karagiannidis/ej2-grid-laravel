<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Query;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;

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
        private bool $ignoreCase = false,
    ) {}

    public function getValue(Grammar $grammar): string
    {
        $postgres = $grammar instanceof PostgresGrammar;
        $column = $grammar->wrap($this->column).($postgres ? '::text' : '');
        $like = $postgres && $this->ignoreCase ? 'ilike' : 'like';

        return sprintf("%s %s%s ? escape '!'", $column, $this->not ? 'not ' : '', $like);
    }
}
