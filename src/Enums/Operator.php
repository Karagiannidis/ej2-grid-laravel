<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Enums;

enum Operator: string
{
    // Equality
    case Equal = 'equal';
    case NotEqual = 'notequal';

    // Comparison
    case GreaterThan = 'greaterthan';
    case GreaterThanOrEqual = 'greaterthanorequal';
    case LessThan = 'lessthan';
    case LessThanOrEqual = 'lessthanorequal';

    // String matching
    case Contains = 'contains';
    case DoesNotContain = 'doesnotcontain';
    case StartsWith = 'startswith';
    case EndsWith = 'endswith';
    case DoesNotStartWith = 'doesnotstartwith';
    case DoesNotEndWith = 'doesnotendwith';

    // Empty / Null
    case IsEmpty = 'isempty';
    case IsNotEmpty = 'isnotempty';
    case IsNull = 'isnull';
    case IsNotNull = 'isnotnull';

    // Set membership
    case In = 'in';
    case NotIn = 'notin';

    // Pattern
    case WildCard = 'wildcard';
    case Like = 'like';
}
