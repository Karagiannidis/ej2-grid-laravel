<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Table(name: 'people')]
final class Person extends Model
{
    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    #[Override]
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
