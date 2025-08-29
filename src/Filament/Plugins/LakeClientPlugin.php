<?php

namespace GustavoCaiano\Lakeclient\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use GustavoCaiano\Lakeclient\Filament\Pages\LicensePage;
use GustavoCaiano\Lakeclient\Http\Middleware\EnsureLicensed;

class LakeClientPlugin implements Plugin
{
    public static function make(): static
    {
        return new static; // @phpstan-ignore-line
    }

    public function getId(): string
    {
        return 'lakeclient';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            LicensePage::class,
        ])->authMiddleware([
            EnsureLicensed::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // No-op for now.
    }
}
