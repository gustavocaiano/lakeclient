<?php

namespace GustavoCaiano\Windclient\Contracts;

interface StateStore
{
    /**
     * @return array<string,mixed>
     */
    public function readState(): array;

    /**
     * @param array<string,mixed> $state
     */
    public function writeState(array $state): void;
}


