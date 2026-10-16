<?php

declare(strict_types=1);

namespace Phit\Tests\Laravel;

use Orchestra\Testbench\TestCase as Orchestra;
use Phit\Laravel\PhitServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [PhitServiceProvider::class];
    }
}
