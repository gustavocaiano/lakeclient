<?php

namespace GustavoCaiano\Windclient\Storage;

use GustavoCaiano\Windclient\Contracts\StateStore;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;

class FileStateStore implements StateStore
{
    private Filesystem $filesystem;

    private EncrypterContract $encrypter;

    private string $storagePath;

    public function __construct(Filesystem $filesystem, EncrypterContract $encrypter)
    {
        $this->filesystem = $filesystem;
        $this->encrypter = $encrypter;
        $this->storagePath = ltrim(Config::get('windclient.storage.path', 'windclient/state.json'), '/'); /** @phpstan-ignore-line */
    }

    public function readState(): array
    {
        try {
            if (! $this->filesystem->exists($this->storagePath)) {
                return [];
            }

            $payload = $this->filesystem->get($this->storagePath);
            $json = $this->encrypter->decrypt($payload);

            /** @var string $json */
            $jsonDecoded = json_decode($json, true);

            /** @var ?array<string,mixed> $jsonDecoded */
            return $jsonDecoded ?: [];
        } catch (FileNotFoundException|\Illuminate\Contracts\Encryption\DecryptException $e) {
            return [];
        }
    }

    public function writeState(array $state): void
    {
        $json = json_encode($state, JSON_PRETTY_PRINT);
        $payload = $this->encrypter->encrypt($json);
        $dir = dirname($this->storagePath);
        if (! $this->filesystem->exists($dir)) {
            $this->filesystem->makeDirectory($dir, 0755, true);
        }
        $this->filesystem->put($this->storagePath, $payload);
    }
}

