<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Fields;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Karagiannidis\Ej2Grid\Field;

/**
 * A value computed in PHP from the row's model. It has no database column, so
 * it can only be displayed, never searched, sorted, filtered or grouped.
 */
final class CustomField extends Field
{
    /**
     * @param  Closure(Model): mixed  $resolver
     */
    public function __construct(string $name, public readonly Closure $resolver)
    {
        parent::__construct($name, null, searchable: false, sortable: false, filterable: false, groupable: false);
    }
}
