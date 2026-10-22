<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Karagiannidis\Ej2Grid\Exceptions\InvalidRequestException;
use Karagiannidis\Ej2Grid\GridQuery;
use Karagiannidis\Ej2Grid\Http\GridRequestParser;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\PersonGrid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Person;

describe('search', function (): void {
    beforeEach(function (): void {
        Person::insert([
            ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 25],
            ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => 30],
            ['name' => 'Carol', 'email' => 'carol@alibaba.com', 'age' => 35],
        ]);
    });

    it('returns the rows where any searched field contains the key', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'search' => [['fields' => ['name', 'email'], 'key' => 'ALI', 'operator' => 'contains', 'ignoreCase' => true]],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Alice', 'Carol']);
    });

    it('matches with the operator set in the grid search settings', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'search' => [['fields' => ['name', 'email'], 'key' => 'c', 'operator' => 'startswith', 'ignoreCase' => true]],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Carol']);
    });

    it('leaves out the columns the grid does not search', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'search' => [['fields' => ['name', 'age'], 'key' => '30', 'operator' => 'contains', 'ignoreCase' => true]],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe([]);
    });

    it('returns only the rows that match both the filters and the search', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [['isComplex' => false, 'field' => 'age', 'operator' => 'greaterthan', 'value' => 26]],
            'search' => [['fields' => ['name', 'email'], 'key' => 'ali', 'operator' => 'contains', 'ignoreCase' => true]],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Carol']);
    });

    it('rejects a search on a field the grid does not define', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'search' => [['fields' => ['name', 'password'], 'key' => 'secret', 'operator' => 'contains', 'ignoreCase' => true]],
        ]);

        $build = fn (): Builder => new GridQuery()->build(new PersonGrid, $request);

        expect($build)->toThrow(InvalidRequestException::class, "Unknown field 'password' at 'search.0.fields.1'.");
    });
});
