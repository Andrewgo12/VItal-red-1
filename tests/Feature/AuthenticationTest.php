<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role'
                    ],
                    'token'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ]);
    }

    public function test_inactive_user_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Usuario inactivo'
            ]);
    }

    public function test_login_validation_errors()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout exitoso'
            ]);

        // Verify token was revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token'
        ]);
    }

    public function test_authenticated_user_can_access_protected_routes()
    {
        $user = User::factory()->create(['role' => 'medico']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/solicitudes-medicas');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/solicitudes-medicas');
        $response->assertStatus(401);
    }

    public function test_invalid_token_returns_401()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token'
        ])->getJson('/api/solicitudes-medicas');

        $response->assertStatus(401);
    }

    public function test_user_profile_endpoint()
    {
        $user = User::factory()->create([
            'role' => 'medico',
            'department' => 'Cardiología',
            'specialties' => ['Cardiología', 'Medicina Interna']
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'department',
                    'specialties',
                    'is_active'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => 'medico'
                ]
            ]);
    }

    public function test_login_rate_limiting()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        // Make multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
        }

        // Next attempt should be rate limited
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_token_expiration()
    {
        $user = User::factory()->create();
        
        // Create an expired token (simulate by setting created_at to past)
        $token = $user->createToken('test-token');
        $token->accessToken->update([
            'created_at' => now()->subMinutes(config('sanctum.expiration', 525600) + 1)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    public function test_multiple_device_login()
    {
        $user = User::factory()->create();

        // Login from first device
        $response1 = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        // Login from second device
        $response2 = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Both tokens should be different
        $this->assertNotEquals(
            $response1->json('data.token'),
            $response2->json('data.token')
        );

        // Both tokens should work
        $token1 = $response1->json('data.token');
        $token2 = $response2->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer ' . $token1])
            ->getJson('/api/auth/user')
            ->assertStatus(200);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token2])
            ->getJson('/api/auth/user')
            ->assertStatus(200);
    }

    public function test_logout_revokes_only_current_token()
    {
        $user = User::factory()->create();

        // Create two tokens
        $token1 = $user->createToken('device1')->plainTextToken;
        $token2 = $user->createToken('device2')->plainTextToken;

        // Logout with first token
        $this->withHeaders(['Authorization' => 'Bearer ' . $token1])
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        // First token should be invalid
        $this->withHeaders(['Authorization' => 'Bearer ' . $token1])
            ->getJson('/api/auth/user')
            ->assertStatus(401);

        // Second token should still work
        $this->withHeaders(['Authorization' => 'Bearer ' . $token2])
            ->getJson('/api/auth/user')
            ->assertStatus(200);
    }
}
