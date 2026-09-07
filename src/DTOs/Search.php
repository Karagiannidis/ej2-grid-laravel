<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\DTOs;

use Karagiannidis\Ej2Grid\Enums\Operator;

final readonly class Search
{
    /**
     * @param  list<string>  $fields
     */
    public function __construct(
        public string $key,
        public array $fields,
        public Operator $operator,
        public bool $ignoreCase,
    ) {}
}
