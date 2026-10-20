<?php

declare(strict_types=1);

use Karagiannidis\Ej2Grid\Http\GridRequestParser;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\PersonGrid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Person;

describe('base query', function (): void {
    it('returns every row the grid query selects', function (): void {
        Person::insert([['name' => 'Alice'], ['name' => 'Bob']]);
        $grid = new PersonGrid;
        $request = new GridRequestParser()->parse([]);

        $names = $grid->buildQuery($request)->pluck('name');

        expect($names->all())->toBe(['Alice', 'Bob']);
    });
});
