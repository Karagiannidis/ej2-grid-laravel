<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Fields;

use Override;

final class BooleanField extends ColumnField
{
    public function __construct(string $name, ?string $column = null)
    {
        parent::__construct($name, $column, searchable: false, sortable: true, filterable: true, groupable: true);
    }

    #[Override]
    protected function format(mixed $value): mixed
    {
        return $value === null ? null : (bool) $value;
    }
}
