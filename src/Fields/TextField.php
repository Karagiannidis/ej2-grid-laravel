<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Fields;

final class TextField extends ColumnField
{
    public function __construct(string $name, ?string $column = null)
    {
        parent::__construct($name, $column, searchable: true, sortable: true, filterable: true, groupable: true);
    }
}
