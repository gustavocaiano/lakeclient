<?php

namespace GustavoCaiano\Windclient\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use GustavoCaiano\Windclient\Filament\Pages\LicensePage;
use GustavoCaiano\Windclient\Http\Middleware\EnsureLicensed;

class WindClientPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'windclient';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            LicensePage::class,
        ])->middleware([
            EnsureLicensed::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // No-op for now.
    }
}
