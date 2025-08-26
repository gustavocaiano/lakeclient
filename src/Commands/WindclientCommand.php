<?php

namespace GustavoCaiano\Windclient\Commands;

use GustavoCaiano\Windclient\Windclient;
use Illuminate\Console\Command;

class WindclientCommand extends Command
{
    public $signature = 'wind:heartbeat';

    public $description = 'Send a heartbeat to renew the license lease';

    public function handle(): int
    {
        /** @var Windclient $client */
        $client = app(Windclient::class);
        $result = $client->heartbeat();
        $this->comment($result['ok'] ? 'Heartbeat OK' : 'Heartbeat failed: '.($result['message'] ?? ''));

        return self::SUCCESS;
    }
}
