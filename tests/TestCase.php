<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use PauloHortelan\LaraCep\LaraCepServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaraCepServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
    }
}
