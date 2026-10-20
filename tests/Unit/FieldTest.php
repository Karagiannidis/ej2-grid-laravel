<?php

declare(strict_types=1);

use Karagiannidis\Ej2Grid\Field;
use Karagiannidis\Ej2Grid\Fields\BooleanField;
use Karagiannidis\Ej2Grid\Fields\CustomField;
use Karagiannidis\Ej2Grid\Fields\DateField;
use Karagiannidis\Ej2Grid\Fields\NumericField;
use Karagiannidis\Ej2Grid\Fields\TextField;

describe('columns', function (): void {
    it('uses the field name as the column when none is given', function (): void {
        $field = new TextField('name');

        expect($field->column)->toBe('name');
    });

    it('reads from a different column when one is given', function (): void {
        $field = new TextField('full_name', 'name');

        expect($field->name)->toBe('full_name')
            ->and($field->column)->toBe('name');
    });

    it('has no column for a custom field', function (): void {
        $field = new CustomField('initials', fn (): string => 'A');

        expect($field->column)->toBeNull();
    });
});

describe('defaults', function (): void {
    it('lets a text field take part in every operation', function (): void {
        $field = new TextField('name');

        expect($field->isSearchable())->toBeTrue()
            ->and($field->isSortable())->toBeTrue()
            ->and($field->isFilterable())->toBeTrue()
            ->and($field->isGroupable())->toBeTrue();
    });

    it('keeps date, numeric and boolean fields out of global search', function (Field $field): void {
        expect($field->isSearchable())->toBeFalse()
            ->and($field->isSortable())->toBeTrue()
            ->and($field->isFilterable())->toBeTrue()
            ->and($field->isGroupable())->toBeTrue();
    })->with([
        'date' => fn (): Field => new DateField('created_at'),
        'numeric' => fn (): Field => new NumericField('age'),
        'boolean' => fn (): Field => new BooleanField('active'),
    ]);

    it('keeps a custom field out of every operation', function (): void {
        $field = new CustomField('initials', fn (): string => 'A');

        expect($field->isSearchable())->toBeFalse()
            ->and($field->isSortable())->toBeFalse()
            ->and($field->isFilterable())->toBeFalse()
            ->and($field->isGroupable())->toBeFalse();
    });
});

describe('modifiers', function (): void {
    it('lets a field opt out of one operation', function (): void {
        $field = new TextField('name')->sortable(false);

        expect($field->isSortable())->toBeFalse()
            ->and($field->isSearchable())->toBeTrue();
    });

    it('lets a field opt in to an operation it is out of by default', function (): void {
        $field = new NumericField('age')->searchable();

        expect($field->isSearchable())->toBeTrue();
    });

    it('takes a non-interactive field out of every operation at once', function (): void {
        $field = new TextField('uuid')->nonInteractive();

        expect($field->isSearchable())->toBeFalse()
            ->and($field->isSortable())->toBeFalse()
            ->and($field->isFilterable())->toBeFalse()
            ->and($field->isGroupable())->toBeFalse();
    });
});
