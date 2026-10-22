<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\DTOs;

final readonly class GridRequest
{
    /**
     * @param  list<Sort>  $sorts
     * @param  list<Search>  $searches
     * @param  list<string>  $groups
     * @param  list<string>  $select
     */
    public function __construct(
        public int $skip,
        public ?int $take,
        public bool $requiresCounts,
        public FilterGroup $filters,
        public array $sorts,
        public array $searches,
        public array $groups,
        public array $select,
        public ?string $distinct,
    ) {}
}
