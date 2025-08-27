<?php

namespace GustavoCaiano\Windclient\Filament\Pages;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use GustavoCaiano\Windclient\Windclient;

class LicensePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static string $view = 'windclient::filament.license-page';

    protected static ?string $navigationLabel = 'License';

    protected static bool $shouldRegisterNavigation = true;

    public ?string $license_key = null;

    public function form(Form $form): Form
    {
        return $form->schema([
            \Filament\Forms\Components\TextInput::make('license_key')
                ->label('License key')
//                ->password()
//                ->revealable()
                ->required(),
        ]);
    }

    public function mount(): void
    {
        /** @var string|null $config */
        $config = config('windclient.license.key');
        $this->license_key = $this->license_key ?: $config;
    }

    public function submit(): void
    {
        /** @var Windclient $client */
        $client = app(Windclient::class);

        try {
            $result = $client->activate($this->license_key ?: null);
            if ($result['ok']) {
                Notification::make()->title('License activated')->success()->send();
                self::setShouldRegisterNavigation(true);
            } else {
                $message = $result['message'] ?? 'Activation failed';
                Notification::make()->title($message)->danger()->send();
            }
        } catch (\Exception $e) {
            Notification::make()->title('Error activating license')
                ->body($e->getMessage())
                ->danger()
                ->color('danger')
                ->send();
        }


    }


    /**
     * @param bool $shouldRegisterNavigation
     */
    public static function setShouldRegisterNavigation(bool $shouldRegisterNavigation): void
    {
        self::$shouldRegisterNavigation = $shouldRegisterNavigation;
    }


    /**
     * @return bool
     */
    public static function isShouldRegisterNavigation(): bool
    {
        /** @var Windclient $client */
        $client = app(Windclient::class);
        return ! $client->isLicensed();
    }
}
