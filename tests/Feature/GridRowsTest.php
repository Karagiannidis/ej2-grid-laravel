<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Karagiannidis\Ej2Grid\Exceptions\InvalidRequestException;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\PersonGrid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\PersonTeamGrid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Person;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Team;

describe('rows for export', function (): void {
    it('returns every row the filters and sorting leave, whatever page is asked for', function (): void {
        Person::insert([
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
            ['name' => 'Carol', 'age' => 35],
            ['name' => 'Dave', 'age' => 40],
        ]);
        $request = Request::create('/', 'POST', [
            'skip' => 1, 'take' => 1,
            'where' => [['isComplex' => false, 'field' => 'age', 'operator' => 'greaterthan', 'value' => 26]],
            'sorted' => [['name' => 'name', 'direction' => 'descending']],
        ]);

        $rows = new PersonGrid()->rows($request);

        expect($rows->pluck('name')->all())->toBe(['Dave', 'Carol', 'Bob']);
    });

    it('shapes each row like the rows the grid answers EJ2 with', function (): void {
        Team::insert(['id' => 1, 'name' => 'Red']);
        Person::insert([['name' => 'Alice', 'team_id' => 1], ['name' => 'Bob', 'team_id' => null]]);
        $request = Request::create('/', 'POST', ['skip' => 0, 'take' => 12]);
        $grid = new PersonTeamGrid;

        $rows = $grid->rows($request);

        expect($rows->all())->toBe($grid->handle($request)->rows);
    });

    it('ignores the page size limit', function (): void {
        Person::insert([['name' => 'Alice'], ['name' => 'Bob']]);
        $request = Request::create('/', 'POST', ['skip' => 0]);

        $rows = new PersonGrid()->rows($request);

        expect($rows->pluck('name')->all())->toBe(['Alice', 'Bob']);
    });

    it('reads the rows a thousand at a time', function (): void {
        Person::insert(array_map(fn (int $i): array => ['name' => 'Person '.$i], range(1, 1001)));
        $request = Request::create('/', 'POST', []);
        DB::enableQueryLog();

        $count = new PersonGrid()->rows($request)->count();

        expect($count)->toBe(1001)
            ->and(DB::getQueryLog())->toHaveCount(2);
    });

    it('rejects an invalid request before reading any row', function (): void {
        $request = Request::create('/', 'POST', [
            'where' => [['isComplex' => false, 'field' => 'password', 'operator' => 'equal', 'value' => 'secret']],
        ]);

        $rows = fn (): LazyCollection => new PersonGrid()->rows($request);

        expect($rows)->toThrow(InvalidRequestException::class, "Unknown field 'password' at 'where.0.field'.");
    });
});
