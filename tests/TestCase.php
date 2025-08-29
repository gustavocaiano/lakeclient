<?php

namespace Tests;

use Filament\Facades\Filament;
use Filament\Panel;
use GustavoCaiano\Lakeclient\LakeclientServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'GustavoCaiano\\Lakeclient\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Minimal Filament panel for rendering pages in tests (no registry needed)
        Filament::setCurrentPanel(Panel::make()->id('app')->default(true));
    }

    protected function getPackageProviders($app)
    {
        return [
            \Livewire\LivewireServiceProvider::class,
            \Filament\Support\SupportServiceProvider::class,
            \Filament\Forms\FormsServiceProvider::class,
            \Filament\Widgets\WidgetsServiceProvider::class,
            \Filament\Actions\ActionsServiceProvider::class,
            \Filament\FilamentServiceProvider::class,
            LakeclientServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Ensure encryption is available in tests
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('0', 32)));

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
