<?php

namespace GustavoCaiano\Lakeclient\Commands;

use GustavoCaiano\Lakeclient\Lakeclient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class LakeclientCommand extends Command
{
    public $signature = 'lake:heartbeat';

    public $description = 'Send a heartbeat to renew the license lease';

    public function handle(): int
    {
        // Optional jitter to stagger heartbeats when many instances run simultaneously
        $jitter = (int) Config::get('lakeclient.heartbeat.jitter_seconds', 0);

        /** @var Lakeclient $client */
        $client = app(Lakeclient::class);
        // Only renew when due (close to expiry) or when expiry is unknown
        if (! $client->shouldRenewLease()) {
            $this->comment('Skip: lease not due for renewal');
            return self::SUCCESS;
        }

        // Bound jitter so we never delay beyond expiry and keep a small network margin
        $secondsLeft = $client->secondsUntilLeaseExpiry();
        $networkMargin = (int) Config::get('lakeclient.heartbeat.network_margin_seconds', 2);
        if (is_int($secondsLeft) && $secondsLeft > 0 && $jitter > 0) {
            $maxDelay = max(0, min($jitter, max(0, $secondsLeft - $networkMargin)));
            try {
                $delay = random_int(0, $maxDelay);
                if ($delay > 0) {
                    sleep($delay);
                }
            } catch (\Throwable) {
                // ignore
            }
        }

        $result = $client->heartbeat();
        $this->comment($result['ok'] ? 'Heartbeat OK' : 'Heartbeat failed: '.($result['message'] ?? ''));

        return self::SUCCESS;
    }
}
