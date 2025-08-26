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

    protected static ?string $navigationGroup = 'Wind';

    protected static ?string $navigationLabel = 'License';

    public ?string $license_key = null;

    public function form(Form $form): Form
    {
        return $form->schema([
            \Filament\Forms\Components\TextInput::make('license_key')
                ->label('License key')
                ->password()
                ->revealable()
                ->required(),
        ]);
    }

    public function mount(): void
    {
        $this->license_key = $this->license_key ?: config('windclient.license.key');
    }

    public function submit(): void
    {
        /** @var Windclient $client */
        $client = app(Windclient::class);
        $result = $client->activate($this->license_key ?: null);

        if ($result['ok']) {
            Notification::make()->title('License activated')->success()->send();
        } else {
            $message = $result['message'] ?? 'Activation failed';
            Notification::make()->title($message)->danger()->send();
        }
    }
}
