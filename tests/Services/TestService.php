<?php



namespace Tests\Services;

class TestService
{

    private bool $is_licensed = false;

    public function activate()
    {
        $this->is_licensed = true;
    }

    public function isLicensed(): bool
    {
        return $this->is_licensed;
    }
}
