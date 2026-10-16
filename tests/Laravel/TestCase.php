<?php

declare(strict_types=1);

namespace Phiz\Tests\Laravel;

use Orchestra\Testbench\TestCase as Orchestra;
use Phiz\Laravel\PhizServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [PhizServiceProvider::class];
    }
}
