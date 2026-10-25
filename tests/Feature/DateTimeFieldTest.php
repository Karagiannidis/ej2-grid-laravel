<?php

declare(strict_types=1);

use Karagiannidis\Ej2Grid\Fields\DateTimeField;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Person;

describe('instant values', function (): void {
    afterEach(function (): void {
        date_default_timezone_set('UTC');
    });

    it('reads a stored date-time string in the application timezone', function (): void {
        date_default_timezone_set('Europe/Athens');
        $person = new Person()->forceFill(['last_seen_at' => '2024-03-15 11:30:00']);
        $field = new DateTimeField('lastSeenAt', 'last_seen_at');

        $value = $field->valueFrom($person);

        expect($value)->toBe('2024-03-15T09:30:00Z');
    });
});
