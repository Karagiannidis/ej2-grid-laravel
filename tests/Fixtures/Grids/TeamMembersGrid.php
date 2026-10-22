<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Tests\Fixtures\Grids;

use Illuminate\Database\Eloquent\Builder;
use Karagiannidis\Ej2Grid\DTOs\GridRequest;
use Karagiannidis\Ej2Grid\Fields\TextField;
use Karagiannidis\Ej2Grid\Grid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Team;

/**
 * Reads a relation with many rows per team, which a grid column can't show.
 */
final class TeamMembersGrid extends Grid
{
    protected function query(GridRequest $request): Builder
    {
        return Team::query();
    }

    protected function fields(): array
    {
        return [
            new TextField('name'),
            new TextField('people.name'),
        ];
    }
}
