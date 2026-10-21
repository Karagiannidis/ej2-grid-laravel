<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Fields;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Override;

final class DateTimeField extends ColumnField
{
    /**
     * UTC marked with Z, which EJ2 converts to the consumers local time.
     */
    private const string FORMAT = 'Y-m-d\TH:i:s\Z';

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

        if (! $value instanceof DateTimeInterface) {
            return $value;
        }

        return DateTimeImmutable::createFromInterface($value)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format(self::FORMAT);
    }
}
