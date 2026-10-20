<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

#[Table(name: 'people')]
final class Person extends Model
{
    #[Override]
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
