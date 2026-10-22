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

describe('sorting', function (): void {
    it('sorts rows by the requested field', function (): void {
        Person::insert([['name' => 'Alice'], ['name' => 'Carol'], ['name' => 'Bob']]);
        $request = new GridRequestParser()->parse([
            'requiresCounts' => true, 'skip' => 0, 'take' => 12,
            'sorted' => [['name' => 'name', 'direction' => 'descending']],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Carol', 'Bob', 'Alice']);
    });

    it('sorts by several fields, the one the user sorted first leading', function (): void {
        Person::insert([
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
            ['name' => 'Carol', 'age' => 30],
            ['name' => 'Dave', 'age' => 25],
        ]);
        $request = new GridRequestParser()->parse([
            'requiresCounts' => true, 'skip' => 0, 'take' => 12,
            // The user sorted by age, then by name: EJ2 lists them the other way round.
            'sorted' => [
                ['name' => 'name', 'direction' => 'descending'],
                ['name' => 'age', 'direction' => 'ascending'],
            ],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Dave', 'Bob', 'Carol', 'Alice']);
    });

    it('sorts by the column behind a field named differently', function (): void {
        Person::insert([
            ['name' => 'Alice', 'created_at' => '2024-01-01 00:00:00'],
            ['name' => 'Bob', 'created_at' => '2025-01-01 00:00:00'],
            ['name' => 'Carol', 'created_at' => '2023-01-01 00:00:00'],
        ]);
        $request = new GridRequestParser()->parse([
            'requiresCounts' => true, 'skip' => 0, 'take' => 12,
            'sorted' => [['name' => 'createdAt', 'direction' => 'descending']],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Bob', 'Alice', 'Carol']);
    });

    it('rejects a sort on a field the grid does not define', function (): void {
        $request = new GridRequestParser()->parse([
            'requiresCounts' => true, 'skip' => 0, 'take' => 12,
            'sorted' => [['name' => 'password', 'direction' => 'ascending']],
        ]);

        expect(fn (): Builder => new GridQuery()->build(new PersonGrid, $request))
            ->toThrow(InvalidRequestException::class, "Unknown field 'password' at 'sorted.0.name'");
    });

    it('points at the sort EJ2 sent when one of several is unknown', function (): void {
        $request = new GridRequestParser()->parse([
            'requiresCounts' => true, 'skip' => 0, 'take' => 12,
            'sorted' => [
                ['name' => 'name', 'direction' => 'ascending'],
                ['name' => 'password', 'direction' => 'ascending'],
            ],
        ]);

        expect(fn (): Builder => new GridQuery()->build(new PersonGrid, $request))
            ->toThrow(InvalidRequestException::class, "Unknown field 'password' at 'sorted.1.name'");
    });

    it('rejects a sort on a field that is not sortable', function (string $field): void {
        $request = new GridRequestParser()->parse([
            'requiresCounts' => true, 'skip' => 0, 'take' => 12,
            'sorted' => [['name' => $field, 'direction' => 'ascending']],
        ]);

        expect(fn (): Builder => new GridQuery()->build(new PersonGrid, $request))
            ->toThrow(InvalidRequestException::class, sprintf("Field '%s' at 'sorted.0.name' is not sortable", $field));
    })->with([
        'non-interactive field' => 'id',
        'custom field' => 'initials',
    ]);
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
    it('serves the rows EJ2 asks for to fill the checkbox filter', function (): void {
        Person::insert([['name' => 'Alice'], ['name' => 'Bob']]);
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'take' => 1000]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Alice', 'Bob']);
    });

    it('rejects a page larger than the limit', function (): void {
        $limit = new PersonGrid()->pageSizeLimit();
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 0, 'take' => $limit + 1]);

        expect(fn (): Builder => new GridQuery()->build(new PersonGrid, $request))
            ->toThrow(InvalidRequestException::class, sprintf("'take' to be at most %d, got %d", $limit, $limit + 1));
    });

    it('rejects a request without a take', function (): void {
        $limit = new PersonGrid()->pageSizeLimit();
        $request = new GridRequestParser()->parse([]);

        expect(fn (): Builder => new GridQuery()->build(new PersonGrid, $request))
            ->toThrow(InvalidRequestException::class, sprintf("'take' to be at most %d, got none", $limit));
    });

    it('returns every row without a take when the grid has no limit', function (): void {
        Person::insert([['name' => 'Alice'], ['name' => 'Bob'], ['name' => 'Carol']]);
        $request = new GridRequestParser()->parse([]);

        $names = new GridQuery()->build(new UnpagedPersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Alice', 'Bob', 'Carol']);
    });
});
