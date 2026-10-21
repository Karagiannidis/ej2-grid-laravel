<?php

declare(strict_types=1);

use Karagiannidis\Ej2Grid\GridFetcher;
use Karagiannidis\Ej2Grid\Http\GridRequestParser;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\PersonGrid;
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

    it('leaves the count out when the request does not ask for it', function (): void {
        $request = new GridRequestParser()->parse(['skip' => 0, 'take' => 2]);

        $result = new GridFetcher()->fetch(new PersonGrid, $request);

        expect($result->count)->toBeNull();
    });
});
