<?php

declare(strict_types=1);

use Karagiannidis\Ej2Grid\DTOs\GridResult;
use Karagiannidis\Ej2Grid\Exceptions\InvalidGridException;
use Karagiannidis\Ej2Grid\GridFetcher;
use Karagiannidis\Ej2Grid\Http\GridRequestParser;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\PersonTeamGrid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\TeamMembersGrid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Person;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Team;

describe('related values', function (): void {
    beforeEach(function (): void {
        Team::insert([['id' => 1, 'name' => 'Red'], ['id' => 2, 'name' => 'Blue']]);
        Person::insert([
            ['name' => 'Alice', 'team_id' => 1],
            ['name' => 'Bob', 'team_id' => 2],
            ['name' => 'Carol', 'team_id' => null],
        ]);
    });

    it('returns related values nested the way EJ2 reads them', function (): void {
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 0, 'take' => 12]);

        $result = new GridFetcher()->fetch(new PersonTeamGrid, $request);

        expect($result->rows)->toBe([
            ['name' => 'Alice', 'team' => ['id' => 1, 'name' => 'Red']],
            ['name' => 'Bob', 'team' => ['id' => 2, 'name' => 'Blue']],
            ['name' => 'Carol', 'team' => ['id' => null, 'name' => null]],
        ])->and($result->count)->toBe(3);
    });

    it('sorts by a related field', function (): void {
        $request = new GridRequestParser()->parse([
            'requiresCounts' => true, 'skip' => 0, 'take' => 12,
            'sorted' => [['name' => 'team.name', 'direction' => 'descending']],
            // Databases disagree on where empty cells sort, so leave Carol out.
            'where' => [['isComplex' => false, 'field' => 'team.name', 'operator' => 'isnotnull', 'value' => null]],
        ]);

        $result = new GridFetcher()->fetch(new PersonTeamGrid, $request);

        expect(array_column($result->rows, 'name'))->toBe(['Alice', 'Bob']);
    });

    it('filters by a related field', function (): void {
        $request = new GridRequestParser()->parse([
            'requiresCounts' => true, 'skip' => 0, 'take' => 12,
            'where' => [['isComplex' => false, 'field' => 'team.name', 'operator' => 'equal', 'value' => 'Blue']],
        ]);

        $result = new GridFetcher()->fetch(new PersonTeamGrid, $request);

        expect(array_column($result->rows, 'name'))->toBe(['Bob']);
    });

    it('rejects a relation with many rows per grid row', function (): void {
        $request = new GridRequestParser()->parse(['requiresCounts' => true, 'skip' => 0, 'take' => 12]);

        $fetch = fn (): GridResult => new GridFetcher()->fetch(new TeamMembersGrid, $request);

        expect($fetch)->toThrow(
            InvalidGridException::class,
            "Field 'people.name' reads relation 'people', which returns Illuminate\\Database\\Eloquent\\Relations\\HasMany. Only a BelongsTo holds one value per row.",
        );
    });
});
