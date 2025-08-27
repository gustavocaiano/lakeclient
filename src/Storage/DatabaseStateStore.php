<?php

namespace GustavoCaiano\Windclient\Storage;

use GustavoCaiano\Windclient\Contracts\StateStore;
use GustavoCaiano\Windclient\Models\WindclientState;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class DatabaseStateStore implements StateStore
{
    private EncrypterContract $encrypter;

    public function __construct(EncrypterContract $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    public function readState(): array
    {
        $row = WindclientState::query()->first();
        if (! $row || ! $row->payload) {
            return [];
        }
        try {
            $json = $this->encrypter->decrypt($row->payload);

            /** @var string $json */
            $jsonDecoded = json_decode($json, true);

            /** @var ?array<string,mixed> $jsonDecoded */
            return $jsonDecoded ?: [];
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return [];
        }
    }

    public function writeState(array $state): void
    {
        $payload = $this->encrypter->encrypt(json_encode($state));
        $row = WindclientState::query()->first() ?: new WindclientState;
        $row->payload = $payload;
        $row->save();
    }
}


