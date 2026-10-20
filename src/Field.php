<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid;

abstract class Field
{
    protected function __construct(public readonly string $name, public readonly ?string $column, private bool $searchable, private bool $sortable, private bool $filterable, private bool $groupable) {}

    final public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    final public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    final public function filterable(bool $filterable = true): static
    {
        $this->filterable = $filterable;

        return $this;
    }

    final public function groupable(bool $groupable = true): static
    {
        $this->groupable = $groupable;

        return $this;
    }

    /**
     * Carry the value to the client without taking part in any grid operation.
     * Typical for ids and uuids that the frontend needs but does not display.
     */
    final public function nonInteractive(): static
    {
        return $this->searchable(false)->sortable(false)->filterable(false)->groupable(false);
    }

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
