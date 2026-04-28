<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_email_returns_true_for_existing_user()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/verify-email', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'exists' => true,
                'message' => 'Email verified',
            ]);
    }

    public function test_verify_email_returns_false_for_non_existing_user()
    {
        $response = $this->postJson('/api/verify-email', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'exists' => false,
                'message' => 'No account found with this email address',
            ]);
    }

    public function test_verify_email_requires_valid_email_format()
    {
        $response = $this->postJson('/api/verify-email', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_verify_email_requires_email_field()
    {
        $response = $this->postJson('/api/verify-email', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
