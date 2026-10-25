<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Tests\Fixtures\Grids;

use Illuminate\Database\Eloquent\Builder;
use Karagiannidis\Ej2Grid\DTOs\GridRequest;
use Karagiannidis\Ej2Grid\Fields\TextField;
use Karagiannidis\Ej2Grid\Grid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Passport;

final class PassportGrid extends Grid
{
    protected function query(GridRequest $request): Builder
    {
        return Passport::query();
    }

    protected function fields(): array
    {
        return [
            new TextField('number'),
            new TextField('person.name'),
            new TextField('person.team.name'),
        ];
    }
}
