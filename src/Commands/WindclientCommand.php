<?php

namespace GustavoCaiano\Windclient\Commands;

use Illuminate\Console\Command;

class WindclientCommand extends Command
{
    public $signature = 'windclient';

    public $description = 'Windclient command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
