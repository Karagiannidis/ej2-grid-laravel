<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid;

use Illuminate\Database\Eloquent\Model;

abstract class Field
{
    protected function __construct(public readonly string $name, protected bool $searchable, protected bool $sortable, protected bool $filterable, protected bool $groupable) {}

    abstract public function valueFrom(Model $model): mixed;

    final public function isSearchable(): bool
    {
        return $this->searchable;
    }

    final public function isSortable(): bool
    {
        return $this->sortable;
    }

    final public function isFilterable(): bool
    {
        return $this->filterable;
    }

    final public function isGroupable(): bool
    {
        return $this->groupable;
    }
}
