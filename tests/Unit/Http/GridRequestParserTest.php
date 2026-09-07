<?php

declare(strict_types=1);

use Karagiannidis\Ej2Grid\DTOs\FilterGroup;
use Karagiannidis\Ej2Grid\DTOs\FilterPredicate;
use Karagiannidis\Ej2Grid\DTOs\Search;
use Karagiannidis\Ej2Grid\DTOs\Sort;
use Karagiannidis\Ej2Grid\Enums\Condition;
use Karagiannidis\Ej2Grid\Enums\Operator;
use Karagiannidis\Ej2Grid\Enums\SortDirection;
use Karagiannidis\Ej2Grid\Exceptions\InvalidRequestException;
use Karagiannidis\Ej2Grid\Http\GridRequestParser;

beforeEach(function (): void {
    $this->parser = new GridRequestParser;
});

describe('valid payloads', function (): void {
    it('parses an initial request with nothing but paging', function (): void {
        $payload = ['requiresCounts' => true, 'skip' => 0, 'take' => 12];

        $request = $this->parser->parse($payload);

        expect($request->skip)->toBe(0)
            ->and($request->take)->toBe(12)
            ->and($request->requiresCounts)->toBeTrue()
            ->and($request->filters)->toEqual(new FilterGroup(Condition::And, []))
            ->and($request->sorts)->toBe([])
            ->and($request->searches)->toBe([])
            ->and($request->groups)->toBe([])
            ->and($request->select)->toBe([]);
    });

    it('parses a request without paging keys as unpaged', function (): void {
        $payload = [];

        $request = $this->parser->parse($payload);

        expect($request->skip)->toBe(0)
            ->and($request->take)->toBeNull()
            ->and($request->requiresCounts)->toBeFalse();
    });

    it('parses a request for a later page', function (): void {
        $payload = ['requiresCounts' => true, 'skip' => 24, 'take' => 12];

        $request = $this->parser->parse($payload);

        expect($request->skip)->toBe(24)
            ->and($request->take)->toBe(12);
    });

    it('parses a single column sort', function (): void {
        $payload = [
            'requiresCounts' => true,
            'skip' => 0,
            'take' => 12,
            'sorted' => [['name' => 'created_at', 'direction' => 'descending']],
        ];

        $request = $this->parser->parse($payload);

        expect($request->sorts)->toEqual([new Sort('created_at', SortDirection::Descending)]);
    });

    it('parses multi-column sorts in the order the user applied them', function (): void {
        $payload = [
            'sorted' => [
                ['name' => 'status', 'direction' => 'ascending'],
                ['name' => 'name', 'direction' => 'descending'],
            ],
        ];

        $request = $this->parser->parse($payload);

        expect($request->sorts)->toEqual([
            new Sort('status', SortDirection::Ascending),
            new Sort('name', SortDirection::Descending),
        ]);
    });

    it('parses a checkbox filter with two values ticked', function (): void {
        $payload = [
            'requiresCounts' => true,
            'skip' => 0,
            'take' => 12,
            'where' => [[
                'isComplex' => true,
                'condition' => 'or',
                'predicates' => [
                    ['isComplex' => false, 'field' => 'name', 'operator' => 'equal', 'value' => 'Alice', 'ignoreCase' => true],
                    ['isComplex' => false, 'field' => 'name', 'operator' => 'equal', 'value' => 'Bob', 'ignoreCase' => true],
                ],
            ]],
        ];

        $request = $this->parser->parse($payload);

        expect($request->filters)->toEqual(new FilterGroup(Condition::And, [
            new FilterGroup(Condition::Or, [
                new FilterPredicate('name', Operator::Equal, 'Alice', true),
                new FilterPredicate('name', Operator::Equal, 'Bob', true),
            ]),
        ]));
    });

    it('parses filters on two columns as nested groups', function (): void {
        $payload = [
            'where' => [[
                'isComplex' => true,
                'condition' => 'and',
                'predicates' => [
                    [
                        'isComplex' => true,
                        'condition' => 'or',
                        'predicates' => [
                            ['isComplex' => false, 'field' => 'name', 'operator' => 'equal', 'value' => 'Alice', 'ignoreCase' => false],
                            ['isComplex' => false, 'field' => 'name', 'operator' => 'equal', 'value' => 'Bob', 'ignoreCase' => false],
                        ],
                    ],
                    ['isComplex' => false, 'field' => 'age', 'operator' => 'greaterthan', 'value' => 26, 'ignoreCase' => false],
                ],
            ]],
        ];

        $request = $this->parser->parse($payload);

        expect($request->filters)->toEqual(new FilterGroup(Condition::And, [
            new FilterGroup(Condition::And, [
                new FilterGroup(Condition::Or, [
                    new FilterPredicate('name', Operator::Equal, 'Alice', false),
                    new FilterPredicate('name', Operator::Equal, 'Bob', false),
                ]),
                new FilterPredicate('age', Operator::GreaterThan, 26, false),
            ]),
        ]));
    });

    it('parses an in filter keeping its value list intact', function (): void {
        $payload = [
            'where' => [['isComplex' => false, 'field' => 'name', 'operator' => 'in', 'value' => ['Alice', 'Bob']]],
        ];

        $request = $this->parser->parse($payload);

        expect($request->filters->predicates[0])
            ->toEqual(new FilterPredicate('name', Operator::In, ['Alice', 'Bob'], false));
    });

    it('parses a null value so blank filters can be told apart from missing ones', function (): void {
        $payload = [
            'where' => [['isComplex' => false, 'field' => 'name', 'operator' => 'equal', 'value' => null, 'ignoreCase' => true]],
        ];

        $request = $this->parser->parse($payload);

        expect($request->filters->predicates[0])
            ->toEqual(new FilterPredicate('name', Operator::Equal, null, true));
    });

    it('parses a global search across several columns', function (): void {
        $payload = [
            'requiresCounts' => true,
            'skip' => 0,
            'take' => 12,
            'search' => [['fields' => ['name', 'email'], 'key' => 'alice', 'operator' => 'contains', 'ignoreCase' => true]],
        ];

        $request = $this->parser->parse($payload);

        expect($request->searches)->toEqual([new Search('alice', ['name', 'email'], Operator::Contains, true)]);
    });

    it('parses a grouped column together with the sort EJ2 adds for it', function (): void {
        $payload = [
            'requiresCounts' => true,
            'skip' => 0,
            'take' => 12,
            'group' => ['status'],
            'sorted' => [['name' => 'status', 'direction' => 'ascending']],
        ];

        $request = $this->parser->parse($payload);

        expect($request->groups)->toBe(['status'])
            ->and($request->sorts)->toEqual([new Sort('status', SortDirection::Ascending)]);
    });

    it('parses a distinct values request from a filter dropdown', function (): void {
        $payload = ['select' => ['status'], 'skip' => 0, 'take' => 3000];

        $request = $this->parser->parse($payload);

        expect($request->select)->toBe(['status'])
            ->and($request->take)->toBe(3000);
    });
});

describe('malformed payloads', function (): void {
    it('rejects a negative skip', function (): void {
        $payload = ['skip' => -1];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "'skip' to be at least 0");
    });

    it('rejects a take below one', function (): void {
        $payload = ['take' => 0];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "'take' to be at least 1");
    });

    it('rejects paging values that are not integers', function (): void {
        $payload = ['skip' => '10'];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "an integer at 'skip'");
    });

    it('rejects a requiresCounts flag that is not a boolean', function (): void {
        $payload = ['requiresCounts' => 'true'];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "a boolean at 'requiresCounts'");
    });

    it('rejects a where clause that is not a list', function (): void {
        $payload = ['where' => 'name'];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "a list at 'where'");
    });

    it('rejects a predicate that is not an object', function (): void {
        $payload = ['where' => ['name']];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "a predicate at 'where.0'");
    });

    it('rejects a predicate without a field', function (): void {
        $payload = ['where' => [['isComplex' => false, 'operator' => 'equal']]];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "a string at 'where.0.field'");
    });

    it('rejects a filter operator EJ2 does not define', function (): void {
        $payload = ['where' => [['isComplex' => false, 'field' => 'name', 'operator' => 'matches']]];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "operator 'matches' at 'where.0.operator'");
    });

    it('rejects a group without its predicates list', function (): void {
        $payload = ['where' => [['isComplex' => true, 'condition' => 'or']]];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "a list at 'where.0.predicates'");
    });

    it('rejects a group condition other than and or or', function (): void {
        $payload = ['where' => [['isComplex' => true, 'condition' => 'xor', 'predicates' => []]]];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "condition 'xor' at 'where.0.condition'");
    });

    it('rejects a sort that is not an object', function (): void {
        $payload = ['sorted' => ['name']];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "a sort at 'sorted.0'");
    });

    it('rejects a sort direction other than ascending or descending', function (): void {
        $payload = ['sorted' => [['name' => 'name', 'direction' => 'asc']]];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "direction 'asc' at 'sorted.0.direction'");
    });

    it('rejects a search that is not an object', function (): void {
        $payload = ['search' => ['alice']];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "a search at 'search.0'");
    });

    it('rejects search fields that are not a list of strings', function (): void {
        $payload = ['search' => [['key' => 'alice', 'fields' => [123], 'operator' => 'contains']]];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "a string at 'search.0.fields.0'");
    });

    it('rejects a group column that is not a string', function (): void {
        $payload = ['group' => [null]];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "a string at 'group.0'");
    });

    it('rejects a list-valued key that is not a list', function (): void {
        $payload = ['sorted' => 'name'];

        expect(fn () => $this->parser->parse($payload))
            ->toThrow(InvalidRequestException::class, "a list at 'sorted'");
    });
});
