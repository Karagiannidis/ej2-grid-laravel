<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Fields;

use DateTimeImmutable;
use DateTimeInterface;
use Override;

final class DateField extends ColumnField
{
    /**
     * EJ2 only parses ISO dates that carry a time, and
     * reads one without a zone as local, so the day never shifts.
     */
    private const string FORMAT = 'Y-m-d\T00:00:00';

    public function __construct(string $name, ?string $column = null)
    {
        parent::__construct($name, $column, searchable: false, sortable: true, filterable: true, groupable: true);
    }

    #[Override]
    protected function format(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = new DateTimeImmutable($value);
        }

        return $value instanceof DateTimeInterface ? $value->format(self::FORMAT) : $value;
    }
}
