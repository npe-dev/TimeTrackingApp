<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestApiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_api_request_without_json_accept_returns_401(): void
    {
        // Plain browser-style request: no Accept: application/json header.
        $this->get('/api/user')->assertStatus(401);
    }

    public function test_guest_api_request_with_json_accept_returns_401(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
    }
}
