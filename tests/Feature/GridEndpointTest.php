<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Karagiannidis\Ej2Grid\DTOs\GridResult;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Grids\PersonGrid;
use Karagiannidis\Ej2Grid\Tests\Fixtures\Models\Person;

beforeEach(function (): void {
    Route::post('people', fn (Request $request): GridResult => new PersonGrid()->handle($request));
});

it('answers an EJ2 request with the page and the total count', function (): void {
    Person::insert([['name' => 'Alice'], ['name' => 'Bob'], ['name' => 'Carol']]);

    $response = $this->postJson('people', ['requiresCounts' => true, 'skip' => 0, 'take' => 2]);

    $response->assertOk()
        ->assertJsonPath('result.*.name', ['Alice', 'Bob'])
        ->assertJsonPath('count', 3);
});

it('answers with a plain list of rows when EJ2 does not ask for a count', function (): void {
    Person::insert([['name' => 'Alice'], ['name' => 'Bob']]);

    $response = $this->postJson('people', ['skip' => 0, 'take' => 12]);

    $response->assertOk()
        ->assertJsonPath('*.name', ['Alice', 'Bob']);
});

it('rejects an invalid request with a 422 and the reason', function (): void {
    $response = $this->postJson('people', ['skip' => 0, 'take' => 501]);

    $response->assertUnprocessable()
        ->assertJsonPath('message', "Expected 'take' to be at most 500, got 501.");
});
