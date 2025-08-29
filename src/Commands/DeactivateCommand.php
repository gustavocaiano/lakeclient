<?php

namespace GustavoCaiano\Lakeclient\Commands;

use GustavoCaiano\Lakeclient\Lakeclient;
use Illuminate\Console\Command;

class DeactivateCommand extends Command
{
    public $signature = 'lake:deactivate';

    public $description = 'Deactivate license activation on this installation';

    public function handle(): int
    {
        /** @var Lakeclient $client */
        $client = app(Lakeclient::class);
        $result = $client->deactivate();
        $this->comment($result['ok'] ? 'Deactivated' : 'Deactivation failed: '.($result['message'] ?? ''));

        return self::SUCCESS;
    }
}
