<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Enums;

enum Condition: string
{
    case And = 'and';
    case Or = 'or';
}
