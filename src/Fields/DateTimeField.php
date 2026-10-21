<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Fields;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Karagiannidis\Ej2Grid\Exceptions\InvalidRequestException;
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

    /**
     * EJ2 sends an instant as JSON writes a date, in UTC with or without
     * milliseconds. It is compared in the application timezone.
     */
    #[Override]
    protected function parse(mixed $value, string $path): mixed
    {
        $instant = is_string($value)
            ? DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.vP', $value) ?: DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sP', $value)
            : false;

        if ($instant === false) {
            throw InvalidRequestException::expectedInstant($path, $value);
        }

        return $instant->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');
    }
}
