<?php

use Karagiannidis\Ej2Grid\PackageServiceProvider;

it('loads the package service provider', function () {
	expect($this->app->providerIsLoaded(PackageServiceProvider::class))->toBeTrue();
});
