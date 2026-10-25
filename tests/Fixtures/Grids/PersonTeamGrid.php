<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Tests\Fixtures\Grids;

use Illuminate\Database\Eloquent\Builder;
use Karagiannidis\Ej2Grid\DTOs\GridRequest;
use Karagiannidis\Ej2Grid\Fields\BooleanField;
use Karagiannidis\Ej2Grid\Fields\DateField;
use Karagiannidis\Ej2Grid\Fields\NumericField;
use Karagiannidis\Ej2Grid\Fields\TextField;
use Karagiannidis\Ej2Grid\Grid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Person;

final class PersonTeamGrid extends Grid
{
    protected function query(GridRequest $request): Builder
    {
        return Person::query();
    }

    protected function fields(): array
    {
        return [
            new TextField('name'),
            new NumericField('team.id'),
            new TextField('team.name'),
            new BooleanField('team.active'),
            new DateField('team.foundedOn', 'founded_on'),
        ];
    }
}
