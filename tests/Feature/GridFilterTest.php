<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Karagiannidis\Ej2Grid\Exceptions\InvalidRequestException;
use Karagiannidis\Ej2Grid\GridQuery;
use Karagiannidis\Ej2Grid\Http\GridRequestParser;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\PersonGrid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Person;

describe('equality', function (): void {
    beforeEach(function (): void {
        Person::insert([
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
            ['name' => 'Carol', 'age' => null],
        ]);
    });

    it('returns the rows equal to the value', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [['isComplex' => false, 'field' => 'age', 'operator' => 'equal', 'value' => 30]],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Bob']);
    });

    it('returns the rows with an empty cell when the user ticks Blanks', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [['isComplex' => false, 'field' => 'age', 'operator' => 'equal', 'value' => null]],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Carol']);
    });

    it('returns the rows that differ from the value, empty cells included', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [['isComplex' => false, 'field' => 'age', 'operator' => 'notequal', 'value' => 30]],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Alice', 'Carol']);
    });

    it('returns the rows with a value when the user leaves out Blanks', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [['isComplex' => false, 'field' => 'age', 'operator' => 'notequal', 'value' => null]],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Alice', 'Bob']);
    });
});

describe('comparison', function (): void {
    beforeEach(function (): void {
        Person::insert([
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
            ['name' => 'Carol', 'age' => 35],
        ]);
    });

    it('returns the rows within a range, bounds included', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [
                ['isComplex' => false, 'field' => 'age', 'operator' => 'greaterthanorequal', 'value' => 30],
                ['isComplex' => false, 'field' => 'age', 'operator' => 'lessthanorequal', 'value' => 35],
            ],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Bob', 'Carol']);
    });

    it('returns the rows within a range, bounds excluded', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [
                ['isComplex' => false, 'field' => 'age', 'operator' => 'greaterthan', 'value' => 25],
                ['isComplex' => false, 'field' => 'age', 'operator' => 'lessthan', 'value' => 35],
            ],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Bob']);
    });

    it('ignores a range bound the user left empty', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [
                ['isComplex' => false, 'field' => 'age', 'operator' => 'greaterthanorequal', 'value' => null],
                ['isComplex' => false, 'field' => 'age', 'operator' => 'lessthanorequal', 'value' => 30],
            ],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Alice', 'Bob']);
    });
});

describe('conditions', function (): void {
    beforeEach(function (): void {
        Person::insert([
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
            ['name' => 'Carol', 'age' => null],
            ['name' => 'Dave', 'age' => null],
        ]);
    });

    it('returns the rows matching any value ticked in a checkbox filter', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [[
                'isComplex' => true,
                'condition' => 'or',
                'predicates' => [
                    ['isComplex' => false, 'field' => 'name', 'operator' => 'equal', 'value' => 'Alice'],
                    ['isComplex' => false, 'field' => 'name', 'operator' => 'equal', 'value' => 'Carol'],
                ],
            ]],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Alice', 'Carol']);
    });

    it('returns the rows matching the filters on every column', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [[
                'isComplex' => true,
                'condition' => 'and',
                'predicates' => [
                    [
                        'isComplex' => true,
                        'condition' => 'or',
                        'predicates' => [
                            ['isComplex' => false, 'field' => 'name', 'operator' => 'equal', 'value' => 'Alice'],
                            ['isComplex' => false, 'field' => 'name', 'operator' => 'equal', 'value' => 'Bob'],
                        ],
                    ],
                    ['isComplex' => false, 'field' => 'age', 'operator' => 'greaterthan', 'value' => 26],
                ],
            ]],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Bob']);
    });

    it('keeps the empty cells a not-equal filter matches from widening other filters', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [
                ['isComplex' => false, 'field' => 'name', 'operator' => 'equal', 'value' => 'Carol'],
                ['isComplex' => false, 'field' => 'age', 'operator' => 'notequal', 'value' => 30],
            ],
        ]);

        $names = new GridQuery()->build(new PersonGrid, $request)->pluck('name');

        expect($names->all())->toBe(['Carol']);
    });
});

describe('invalid filters', function (): void {
    it('rejects a filter on a field the grid does not define', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [[
                'isComplex' => true,
                'condition' => 'and',
                'predicates' => [
                    ['isComplex' => false, 'field' => 'password', 'operator' => 'equal', 'value' => 'secret'],
                ],
            ]],
        ]);

        expect(fn (): Builder => new GridQuery()->build(new PersonGrid, $request))
            ->toThrow(InvalidRequestException::class, "Unknown field 'password' at 'where.0.predicates.0.field'");
    });

    it('rejects a filter on a field that is not filterable', function (string $field): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [['isComplex' => false, 'field' => $field, 'operator' => 'equal', 'value' => 'A']],
        ]);

        expect(fn (): Builder => new GridQuery()->build(new PersonGrid, $request))
            ->toThrow(InvalidRequestException::class, sprintf("Field '%s' at 'where.0.field' is not filterable", $field));
    })->with([
        'non-interactive field' => 'id',
        'custom field' => 'initials',
    ]);

    it('rejects a filter operator that is not supported', function (): void {
        $request = new GridRequestParser()->parse([
            'skip' => 0, 'take' => 12,
            'where' => [['isComplex' => false, 'field' => 'name', 'operator' => 'contains', 'value' => 'li']],
        ]);

        expect(fn (): Builder => new GridQuery()->build(new PersonGrid, $request))
            ->toThrow(InvalidRequestException::class, "Filter operator 'contains' at 'where.0.operator' is not supported");
    });
});
