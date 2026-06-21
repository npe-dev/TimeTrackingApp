<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_user_can_create_list_and_revoke_tokens(): void
    {
        $user = User::factory()->create();

        // Create — secret returned once.
        $create = $this->actingAs($user)
            ->postJson('/api/tokens', ['name' => 'Claude Code'])
            ->assertCreated()
            ->assertJsonStructure(['id', 'name', 'plain_text_token']);

        $id = $create->json('id');

        // List — never exposes the secret.
        $this->actingAs($user)
            ->getJson('/api/tokens')
            ->assertSuccessful()
            ->assertJsonFragment(['name' => 'Claude Code'])
            ->assertJsonMissing(['plain_text_token' => $create->json('plain_text_token')]);

        // Revoke.
        $this->actingAs($user)
            ->deleteJson("/api/tokens/{$id}")
            ->assertSuccessful();

        $this->assertCount(0, $user->tokens()->get());
    }

    public function test_bearer_token_cannot_manage_tokens(): void
    {
        $user = User::factory()->create();
        $plain = $user->createToken('mcp')->plainTextToken;

        // Using the bearer token (not a browser session) must be forbidden.
        $this->withHeader('Authorization', "Bearer {$plain}")
            ->postJson('/api/tokens', ['name' => 'sneaky'])
            ->assertStatus(403);

        $this->withHeader('Authorization', "Bearer {$plain}")
            ->getJson('/api/tokens')
            ->assertStatus(403);
    }
}
