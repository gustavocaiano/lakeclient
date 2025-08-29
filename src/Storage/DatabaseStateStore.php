<?php

namespace GustavoCaiano\Lakeclient\Storage;

use GustavoCaiano\Lakeclient\Contracts\StateStore;
use GustavoCaiano\Lakeclient\Models\LakeclientState;
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
        $row = LakeclientState::query()->first();
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
        $row = LakeclientState::query()->first() ?: new LakeclientState;
        $row->payload = $payload;
        $row->save();
    }
}


