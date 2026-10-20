<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Fields;

final class BooleanField extends ColumnField
{
    public function __construct(string $name, ?string $column = null)
    {
        parent::__construct($name, $column, searchable: false, sortable: true, filterable: true, groupable: true);
    }
}
