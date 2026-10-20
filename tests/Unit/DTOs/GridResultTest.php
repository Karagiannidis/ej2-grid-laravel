<?php

declare(strict_types=1);

use Karagiannidis\Ej2Grid\DTOs\GridResult;

it('serializes to result and count when there is a count', function (): void {
    $result = new GridResult([['name' => 'Alice']], 5);

    $json = json_encode($result);

    expect($json)->toBe('{"result":[{"name":"Alice"}],"count":5}');
});

it('serializes to a plain list of rows when there is no count', function (): void {
    $result = new GridResult([['name' => 'Alice']], null);

    $json = json_encode($result);

    expect($json)->toBe('[{"name":"Alice"}]');
});
