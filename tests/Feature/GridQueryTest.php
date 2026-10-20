<?php

declare(strict_types=1);

use Karagiannidis\Ej2Grid\GridQuery;
use Karagiannidis\Ej2Grid\Http\GridRequestParser;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\PersonGrid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Person;

describe('base query', function (): void {
    it('returns every row the grid query selects', function (): void {
        Person::insert([['name' => 'Alice'], ['name' => 'Bob']]);
        $request = new GridRequestParser()->parse([]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Alice', 'Bob']);
    });
});

describe('ordering', function (): void {
    it('returns rows in primary key order when the request has no sort', function (): void {
        Person::insert([
            ['id' => 1, 'name' => 'Carol'],
            ['id' => 2, 'name' => 'Alice'],
            ['id' => 3, 'name' => 'Bob'],
        ]);
        $request = new GridRequestParser()->parse([]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Carol', 'Alice', 'Bob']);
    });
});

describe('paging', function (): void {
    beforeEach(function (): void {
        Person::insert([
            ['name' => 'Alice'],
            ['name' => 'Bob'],
            ['name' => 'Carol'],
            ['name' => 'Dave'],
            ['name' => 'Erin'],
        ]);
    });

    it('returns the requested page', function (): void {
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 2, 'take' => 2]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Carol', 'Dave']);
    });

    it('returns a short last page', function (): void {
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 4, 'take' => 2]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Erin']);
    });

    it('returns no rows for a page past the end', function (): void {
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 10, 'take' => 2]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe([]);
    });

    it('returns every row when the request has no take', function (): void {
        $request = new GridRequestParser()->parse([]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Alice', 'Bob', 'Carol', 'Dave', 'Erin']);
    });
});
