<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Fields;

use Karagiannidis\Ej2Grid\Field;

/**
 * A field backed by a database column, so it can take part in grid operations.
 */
abstract class ColumnField extends Field
{
    public readonly string $column;

    protected function __construct(string $name, ?string $column, bool $searchable, bool $sortable, bool $filterable, bool $groupable)
    {
        parent::__construct($name, $searchable, $sortable, $filterable, $groupable);

        $this->column = $column ?? $name;
    }

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

    final public function nonInteractive(): static
    {
        return $this->searchable(false)->sortable(false)->filterable(false)->groupable(false);
    }
}
