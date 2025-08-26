<?php

namespace GustavoCaiano\Windclient\Storage;

use GustavoCaiano\Windclient\Contracts\StateStore;
use GustavoCaiano\Windclient\Models\WindclientState;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Encryption\DecryptException;

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
            return json_decode($json, true) ?: [];
        } catch (DecryptException $e) {
            return [];
        }
    }

    public function writeState(array $state): void
    {
        $payload = $this->encrypter->encrypt(json_encode($state));
        $row = WindclientState::query()->first() ?: new WindclientState();
        $row->payload = $payload;
        $row->save();
    }
}


