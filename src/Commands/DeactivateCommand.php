<?php

namespace GustavoCaiano\Windclient\Commands;

use GustavoCaiano\Windclient\Windclient;
use Illuminate\Console\Command;

class DeactivateCommand extends Command
{
    public $signature = 'wind:deactivate';

    public $description = 'Deactivate license activation on this installation';

    public function handle(): int
    {
        /** @var Windclient $client */
        $client = app(Windclient::class);
        $result = $client->deactivate();
        $this->comment($result['ok'] ? 'Deactivated' : 'Deactivation failed: '.($result['message'] ?? ''));

        return self::SUCCESS;
    }
}
