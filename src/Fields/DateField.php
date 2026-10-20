<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Fields;

use Karagiannidis\Ej2Grid\Field;

final class DateField extends Field
{
    public function __construct(string $name, ?string $column = null)
    {
        parent::__construct($name, $column ?? $name, searchable: false, sortable: true, filterable: true, groupable: true);
    }
}
