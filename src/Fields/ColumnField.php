<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Fields;

use Illuminate\Database\Eloquent\Model;
use Karagiannidis\Ej2Grid\Field;

/**
 * A field backed by a database column, so it can take part in grid operations.
 * A name with dots, like 'team.name', reads the column of a related model.
 */
abstract class ColumnField extends Field
{
    public readonly string $column;

    public readonly ?string $relation;

    protected function __construct(string $name, ?string $column, bool $searchable, bool $sortable, bool $filterable, bool $groupable)
    {
        parent::__construct($name, $searchable, $sortable, $filterable, $groupable);

        $dot = mb_strrpos($name, '.');

        $this->relation = $dot === false ? null : mb_substr($name, 0, $dot);
        $this->column = $column ?? ($dot === false ? $name : mb_substr($name, $dot + 1));
    }

    final public function valueFrom(Model $model): mixed
    {
        return $this->format($model->getAttribute($this->attribute()));
    }

    /**
     * The column with its table, or with the alias its relation is joined as.
     *
     * @internal
     */
    final public function qualifiedColumn(Model $model): string
    {
        return $this->relation === null
            ? $model->qualifyColumn($this->column)
            : $this->relationAlias().'.'.$this->column;
    }

    /**
     * @internal
     */
    final public function attribute(): string
    {
        return $this->relation === null ? $this->column : $this->name;
    }

    /**
     * @internal
     */
    final public function relationAlias(): string
    {
        return str_replace('.', '__', (string) $this->relation);
    }

    /**
     * Turn a value from an EJ2 filter into one the database compares with the
     * column. A null value stays null.
     */
    final public function filterValue(mixed $value, string $path): mixed
    {
        return $value === null ? null : $this->parse($value, $path);
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

    /**
     * Format the column's value for EJ2. Most fields send it unchanged.
     * Date fields override this to send the format EJ2 parses.
     */
    protected function format(mixed $value): mixed
    {
        return $value;
    }

    /**
     * The reverse of format(). Turn a value EJ2 sent into what the column
     * holds. Most fields use it unchanged. Date fields override this.
     */
    protected function parse(mixed $value, string $path): mixed
    {
        return $value;
    }
}
