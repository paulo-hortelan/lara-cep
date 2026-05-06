<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class LaraCepServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('lara-cep')
            ->hasConfigFile();

        $this->app->singleton('lara-cep', function ($app): LaraCepManager {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('lara-cep', []);

            return new LaraCepManager($app['cache'], $config);
        });
    }
}
