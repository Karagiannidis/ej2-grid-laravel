<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Karagiannidis\Ej2Grid\DTOs\GridResult;
use Karagiannidis\Ej2Grid\Exceptions\InvalidRequestException;
use Karagiannidis\Ej2Grid\GridFetcher;
use Karagiannidis\Ej2Grid\Http\GridRequestParser;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\PersonGrid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\UnpagedPersonGrid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Person;

describe('rows', function (): void {
    it('returns every field of a row under the name EJ2 uses for it', function (): void {
        Person::insert([
            'id' => 7,
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'age' => 30,
            'active' => true,
            'birth_date' => '1990-05-20',
            'created_at' => '2024-03-15 09:30:00',
        ]);
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 0, 'take' => 12]);

        $result = new GridFetcher()->fetch(new PersonGrid, $request);

        expect($result->rows)->toBe([[
            'id' => 7,
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'age' => 30,
            'active' => true,
            'birthDate' => '1990-05-20T00:00:00',
            'createdAt' => '2024-03-15T09:30:00Z',
            'initials' => 'A',
        ]]);
    });
});

describe('count', function (): void {
    beforeEach(function (): void {
        Person::insert([
            ['name' => 'Alice'],
            ['name' => 'Bob'],
            ['name' => 'Carol'],
            ['name' => 'Dave'],
            ['name' => 'Erin'],
        ]);
    });

    it('counts every matching row, not just the page', function (): void {
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 0, 'take' => 2]);

        $result = new GridFetcher()->fetch(new PersonGrid, $request);

        expect($result->rows)->toHaveCount(2)
            ->and($result->count)->toBe(5);
    });

    it('counts a page with room to spare from its rows, without a second query', function (): void {
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 4, 'take' => 2]);
        DB::enableQueryLog();

        $result = new GridFetcher()->fetch(new PersonGrid, $request);

        expect($result->rows)->toHaveCount(1)
            ->and($result->count)->toBe(5)
            ->and(DB::getQueryLog())->toHaveCount(1);
    });

    it('counts with the database when the page is empty past the end', function (): void {
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 10, 'take' => 2]);
        DB::enableQueryLog();

        $result = new GridFetcher()->fetch(new PersonGrid, $request);

        expect($result->rows)->toBe([])
            ->and($result->count)->toBe(5)
            ->and(DB::getQueryLog())->toHaveCount(2);
    });

    it('counts the rows of an unpaged grid without a second query', function (): void {
        $request = new GridRequestParser()->parse(['requiresCounts' => true]);
        DB::enableQueryLog();

        $result = new GridFetcher()->fetch(new UnpagedPersonGrid, $request);

        expect($result->count)->toBe(5)
            ->and(DB::getQueryLog())->toHaveCount(1);
    });

    it('counts only the rows the filter matches', function (): void {
        $request = new GridRequestParser()->parse([
            'requiresCounts' => true, 'skip' => 0, 'take' => 1,
            'where' => [[
                'isComplex' => true,
                'condition' => 'or',
                'predicates' => [
                    ['isComplex' => false, 'field' => 'name', 'operator' => 'equal', 'value' => 'Alice'],
                    ['isComplex' => false, 'field' => 'name', 'operator' => 'equal', 'value' => 'Bob'],
                ],
            ]],
        ]);

        $result = new GridFetcher()->fetch(new PersonGrid, $request);

        expect($result->rows)->toHaveCount(1)
            ->and($result->count)->toBe(2);
    });

    it('leaves the count out when the request does not ask for it', function (): void {
        $request = new GridRequestParser()->parse(['skip' => 0, 'take' => 2]);

        $result = new GridFetcher()->fetch(new PersonGrid, $request);

        expect($result->count)->toBeNull();
    });
});

describe('filter dropdown', function (): void {
    it('returns each value of the field once, in order, with how many there are', function (): void {
        Person::insert([
            ['name' => 'Bob', 'age' => 30],
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Carol', 'age' => 30],
        ]);
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'take' => 1000, 'distinct' => 'age']);

        $result = new GridFetcher()->fetch(new PersonGrid, $request);

        expect($result->rows)->toBe([['age' => 25], ['age' => 30]])
            ->and($result->count)->toBe(2);
    });

    it('formats the values the way the grid formats them', function (): void {
        Person::insert([
            ['name' => 'Alice', 'created_at' => '2024-03-15 09:30:00'],
            ['name' => 'Bob', 'created_at' => '2024-03-15 09:30:00'],
        ]);
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'take' => 1000, 'distinct' => 'createdAt']);

        $result = new GridFetcher()->fetch(new PersonGrid, $request);

        expect($result->rows)->toBe([['createdAt' => '2024-03-15T09:30:00Z']]);
    });

    it('lists empty cells once, for the Blanks row, and counts them', function (): void {
        Person::insert([
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => null],
            ['name' => 'Carol', 'age' => null],
        ]);
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'take' => 1000, 'distinct' => 'age']);

        $result = new GridFetcher()->fetch(new PersonGrid, $request);

        // Databases disagree on whether empty cells sort first or last.
        expect($result->rows)->toEqualCanonicalizing([['age' => 30], ['age' => null]])
            ->and($result->count)->toBe(2);
    });

    it('lists only the values left by the filters on other columns', function (): void {
        Person::insert([
            ['name' => 'Alice', 'age' => 25, 'active' => true],
            ['name' => 'Bob', 'age' => 30, 'active' => false],
        ]);
        $request = new GridRequestParser()->parse([
            'requiresCounts' => true, 'take' => 1000, 'distinct' => 'age',
            'where' => [['isComplex' => false, 'field' => 'active', 'operator' => 'equal', 'value' => true]],
        ]);

        $result = new GridFetcher()->fetch(new PersonGrid, $request);

        expect($result->rows)->toBe([['age' => 25]]);
    });

    it('rejects the values of a field the grid does not define', function (): void {
        $request = new GridRequestParser()->parse(['take' => 1000, 'distinct' => 'password']);

        $fetch = fn (): GridResult => new GridFetcher()->fetch(new PersonGrid, $request);

        expect($fetch)->toThrow(InvalidRequestException::class, "Unknown field 'password' at 'distinct'.");
    });

    it('rejects the values of a field the grid does not filter', function (): void {
        $request = new GridRequestParser()->parse(['take' => 1000, 'distinct' => 'initials']);

        $fetch = fn (): GridResult => new GridFetcher()->fetch(new PersonGrid, $request);

        expect($fetch)->toThrow(InvalidRequestException::class, "Field 'initials' at 'distinct' is not filterable.");
    });
});
