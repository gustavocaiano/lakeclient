<?php

namespace GustavoCaiano\Windclient;

use GustavoCaiano\Windclient\Commands\WindclientCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
            ->hasMigration('create_migration_table_name_table')
            ->hasCommand(WindclientCommand::class);
    }
}
