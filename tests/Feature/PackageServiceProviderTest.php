<?php

declare(strict_types=1);

use Karagiannidis\Ej2Grid\PackageServiceProvider;

it('registers the service provider', function (): void {
    expect($this->app->providerIsLoaded(PackageServiceProvider::class))->toBeTrue();
});
