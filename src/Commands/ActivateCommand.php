<?php

namespace GustavoCaiano\Lakeclient\Commands;

use GustavoCaiano\Lakeclient\Lakeclient;
use Illuminate\Console\Command;

class ActivateCommand extends Command
{
    public $signature = 'lake:activate {key?}';

    public $description = 'Activate the license with an optional key argument';

    public function handle(): int
    {
        /** @var Lakeclient $client */
        $client = app(Lakeclient::class);
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
