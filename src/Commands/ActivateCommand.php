<?php

namespace GustavoCaiano\Windclient\Commands;

use GustavoCaiano\Windclient\Windclient;
use Illuminate\Console\Command;

class ActivateCommand extends Command
{
    public $signature = 'wind:activate {key?}';

    public $description = 'Activate the license with an optional key argument';

    public function handle(): int
    {
        /** @var Windclient $client */
        $client = app(Windclient::class);
        $key = $this->argument('key') ?: null;
        $result = $client->activate($key);

        if ($result['ok']) {
            $this->comment('Activation OK');

            return self::SUCCESS;
        }

        $this->error('Activation failed: '.($result['message'] ?? ''));

        return self::FAILURE;
    }
}
