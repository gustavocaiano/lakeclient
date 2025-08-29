<?php

namespace Tests\Feature;

use GustavoCaiano\Lakeclient\Http\Middleware\EnsureLicensed;
use GustavoCaiano\Lakeclient\Lakeclient;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Dummy route protected by middleware
        Route::middleware([EnsureLicensed::class])->get('/protected', function () {
            return 'ok';
        });
    }

    public function test_redirects_when_unlicensed(): void
    {
        // Ensure fresh state
        $client = app(Lakeclient::class);
        $client->writeState([]);

        $response = $this->get('/protected');
        $response->assertRedirect();
    }

    public function test_allows_when_licensed(): void
    {
        $client = app(Lakeclient::class);
        $client->writeState([
            'activation_id' => 'uuid',
            'lease_token' => 'token',
        ]);

        $response = $this->get('/protected');
        $response->assertOk();
        $response->assertSee('ok');
    }
}
