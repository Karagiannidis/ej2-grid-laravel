<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\DTOs;

use Karagiannidis\Ej2Grid\Enums\Condition;

final readonly class FilterGroup
{
    /**
     * @param  list<FilterPredicate|FilterGroup>  $predicates
     */
    public function __construct(
        public Condition $condition,
        public array $predicates,
    ) {}
}
