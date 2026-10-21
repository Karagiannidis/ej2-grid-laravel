<?php

declare(strict_types=1);

use Karagiannidis\Ej2Grid\Field;
use Karagiannidis\Ej2Grid\Fields\BooleanField;
use Karagiannidis\Ej2Grid\Fields\CustomField;
use Karagiannidis\Ej2Grid\Fields\DateField;
use Karagiannidis\Ej2Grid\Fields\DateTimeField;
use Karagiannidis\Ej2Grid\Fields\NumericField;
use Karagiannidis\Ej2Grid\Fields\TextField;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Person;

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

describe('values', function (): void {
    it('shows a calendar date as midnight with no timezone', function (mixed $stored): void {
        $person = new Person()->forceFill(['birth_date' => $stored]);
        $field = new DateField('birthDate', 'birth_date');

        $value = $field->valueFrom($person);

        expect($value)->toBe('1990-05-20T00:00:00');
    })->with([
        'date object' => fn (): DateTimeImmutable => new DateTimeImmutable('1990-05-20 15:45:00'),
        'date string' => '1990-05-20',
        'date-time string' => '1990-05-20 15:45:00',
    ]);

    it("shows an instant in UTC so EJ2 converts it to the viewer's timezone", function (mixed $stored): void {
        $person = new Person()->forceFill(['last_seen_at' => $stored]);
        $field = new DateTimeField('lastSeenAt', 'last_seen_at');

        $value = $field->valueFrom($person);

        expect($value)->toBe('2024-03-15T09:30:00Z');
    })->with([
        'date object in another timezone' => fn (): DateTimeImmutable => new DateTimeImmutable('2024-03-15 11:30:00', new DateTimeZone('Europe/Athens')),
        'string in the application timezone' => '2024-03-15 09:30:00',
    ]);

    it('leaves an empty date empty', function (Field $field): void {
        $person = new Person()->forceFill(['day' => null]);

        $value = $field->valueFrom($person);

        expect($value)->toBeNull();
    })->with([
        'calendar date' => fn (): Field => new DateField('day'),
        'instant' => fn (): Field => new DateTimeField('day'),
    ]);
});
