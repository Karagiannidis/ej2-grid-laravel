<?php

namespace Karagiannidis\Ej2Grid\Tests;

use Karagiannidis\Ej2Grid\PackageServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
	protected function getPackageProviders($app): array
	{
		return [PackageServiceProvider::class];
	}
}
