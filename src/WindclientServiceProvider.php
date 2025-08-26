<?php

namespace GustavoCaiano\Windclient;

use Filament\Facades\Filament;
use GustavoCaiano\Windclient\Commands\DeactivateCommand;
use GustavoCaiano\Windclient\Commands\ActivateCommand;
use GustavoCaiano\Windclient\Commands\WindclientCommand;
use GustavoCaiano\Windclient\Filament\Plugins\WindClientPlugin;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use GustavoCaiano\Windclient\Contracts\StateStore;
use GustavoCaiano\Windclient\Storage\FileStateStore;
use GustavoCaiano\Windclient\Storage\DatabaseStateStore;

class WindclientServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('windclient')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_windclient_table')
            ->hasCommand(WindclientCommand::class)
            ->hasCommand(DeactivateCommand::class)
            ->hasCommand(ActivateCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(StateStore::class, function ($app) {
            $driver = config('windclient.storage.driver', 'file');
            if ($driver === 'database') {
                return new DatabaseStateStore($app['encrypter']);
            }
            return new FileStateStore($app['files'], $app['encrypter']);
        });

        $this->app->singleton(Windclient::class, function ($app) {
            return new Windclient(
                $app->make(StateStore::class),
                $app->make(\GustavoCaiano\Windclient\Http\WindHttpClient::class)
            );
        });

        // Plugin should be registered in the host application's Filament panel provider via ->plugins([...])
    }
}
