<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\DTOs;

use Karagiannidis\Ej2Grid\Enums\SortDirection;

final readonly class Sort
{
    public function __construct(
        public string $field,
        public SortDirection $direction,
    ) {}
}
