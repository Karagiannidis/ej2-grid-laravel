<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\DTOs;

use Karagiannidis\Ej2Grid\Enums\Operator;

final readonly class FilterPredicate
{
    public function __construct(
        public string $field,
        public Operator $operator,
        public mixed $value,
        public bool $ignoreCase,
    ) {}
}
