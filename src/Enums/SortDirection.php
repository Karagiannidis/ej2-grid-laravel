<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Enums;

enum SortDirection: string
{
    case Ascending = 'ascending';
    case Descending = 'descending';

    /**
     * @return 'asc'|'desc'
     */
    public function toSql(): string
    {
        return match ($this) {
            self::Ascending => 'asc',
            self::Descending => 'desc',
        };
    }
}
