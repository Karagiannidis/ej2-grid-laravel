<?php

declare(strict_types=1);

use Karagiannidis\Ej2Grid\Exceptions\InvalidGridException;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\DuplicateFieldGrid;

describe('fields', function (): void {
    it('rejects a grid that defines a field name twice', function (): void {
        $grid = new DuplicateFieldGrid;

        $fieldMap = fn (): array => $grid->fieldMap();

        expect($fieldMap)->toThrow(
            InvalidGridException::class,
            "Grid Karagiannidis\\Ej2Grid\\Tests\\Fixtures\\Grids\\DuplicateFieldGrid defines field 'age' more than once.",
        );
    });
});
