<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Fields;

use DateTimeImmutable;
use DateTimeInterface;
use Karagiannidis\Ej2Grid\Exceptions\InvalidRequestException;
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

    /**
     * Only a plain date is accepted, which EJ2 sends for a
     * column of type 'dateonly'. Type 'date' sends the consumers midnight in
     * UTC, and the day cannot be told without the viewer's timezone.
     */
    #[Override]
    protected function parse(mixed $value, string $path): mixed
    {
        $date = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;

        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw InvalidRequestException::expectedCalendarDate($path, $value);
        }

        return $value;
    }
}
