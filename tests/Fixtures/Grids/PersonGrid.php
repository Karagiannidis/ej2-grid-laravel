<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\Tests\Fixtures\Grids;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Karagiannidis\Ej2Grid\DTOs\GridRequest;
use Karagiannidis\Ej2Grid\Fields\BooleanField;
use Karagiannidis\Ej2Grid\Fields\CustomField;
use Karagiannidis\Ej2Grid\Fields\DateField;
use Karagiannidis\Ej2Grid\Fields\DateTimeField;
use Karagiannidis\Ej2Grid\Fields\NumericField;
use Karagiannidis\Ej2Grid\Fields\TextField;
use Karagiannidis\Ej2Grid\Grid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Person;

final class PersonGrid extends Grid
{
    protected function query(GridRequest $request): Builder
    {
        return Person::query();
    }

    protected function fields(): array
    {
        return [
            new NumericField('id')->nonInteractive(),
            new TextField('name'),
            new TextField('email'),
            new NumericField('age'),
            new BooleanField('active'),
            new DateField('birthDate', 'birth_date'),
            new DateTimeField('createdAt', 'created_at'),
            new CustomField('initials', fn (Model $person): string => mb_substr((string) $person->getAttribute('name'), 0, 1)),
        ];
    }
}
