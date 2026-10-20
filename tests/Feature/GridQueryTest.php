<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Karagiannidis\Ej2Grid\Exceptions\InvalidRequestException;
use Karagiannidis\Ej2Grid\GridQuery;
use Karagiannidis\Ej2Grid\Http\GridRequestParser;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\PersonGrid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\UnpagedPersonGrid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Person;

describe('base query', function (): void {
    it('returns every row the grid query selects', function (): void {
        Person::insert([['name' => 'Alice'], ['name' => 'Bob']]);
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 0, 'take' => 12]);

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
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 0, 'take' => 12]);

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
});

describe('page size limit', function (): void {
    it('serves a page as large as the limit', function (): void {
        Person::insert([['name' => 'Alice'], ['name' => 'Bob']]);
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 0, 'take' => 500]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Alice', 'Bob']);
    });

    it('rejects a page larger than the limit', function (): void {
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 0, 'take' => 501]);

        expect(fn (): Builder => new GridQuery()->build(new PersonGrid, $request))
            ->toThrow(InvalidRequestException::class, "'take' to be at most 500, got 501");
    });

    it('rejects a request without a take', function (): void {
        $request = new GridRequestParser()->parse([]);

        expect(fn (): Builder => new GridQuery()->build(new PersonGrid, $request))
            ->toThrow(InvalidRequestException::class, "'take' to be at most 500, got none");
    });

    it('returns every row without a take when the grid has no limit', function (): void {
        Person::insert([['name' => 'Alice'], ['name' => 'Bob'], ['name' => 'Carol']]);
        $request = new GridRequestParser()->parse([]);

        $names = new GridQuery()->build(new UnpagedPersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Alice', 'Bob', 'Carol']);
    });
});
