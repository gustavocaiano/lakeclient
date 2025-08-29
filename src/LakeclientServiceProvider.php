<?php

namespace GustavoCaiano\Lakeclient;

use Filament\Facades\Filament;
use GustavoCaiano\Lakeclient\Commands\ActivateCommand;
use GustavoCaiano\Lakeclient\Commands\DeactivateCommand;
use GustavoCaiano\Lakeclient\Commands\LakeclientCommand;
use GustavoCaiano\Lakeclient\Contracts\StateStore;
use GustavoCaiano\Lakeclient\Storage\DatabaseStateStore;
use GustavoCaiano\Lakeclient\Storage\FileStateStore;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LakeclientServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('lakeclient')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_lakeclient_table')
            ->hasCommand(LakeclientCommand::class)
            ->hasCommand(DeactivateCommand::class)
            ->hasCommand(ActivateCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(StateStore::class, function ($app) {
            $driver = config('lakeclient.storage.driver', 'file');
            if ($driver === 'database') {
                return new DatabaseStateStore($app['encrypter']);
            }

            return new FileStateStore($app['files'], $app['encrypter']);
        });

        $this->app->singleton(Lakeclient::class, function ($app) {
            return new Lakeclient(
                $app->make(StateStore::class),
                $app->make(\GustavoCaiano\Lakeclient\Http\LakeHttpClient::class)
            );
        });

        // Plugin should be registered in the host application's Filament panel provider via ->plugins([...])
    }
}
