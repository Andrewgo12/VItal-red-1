<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $medico;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'administrador',
            'email' => 'admin@test.com'
        ]);

        $this->medico = User::factory()->create([
            'role' => 'medico',
            'email' => 'medico@test.com'
        ]);
    }

    public function test_admin_can_view_all_users()
    {
        User::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'role',
                            'department',
                            'is_active'
                        ]
                    ]
                ]
            ]);
    }

    public function test_medico_cannot_view_users_list()
    {
        $response = $this->actingAs($this->medico)
            ->get('/api/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_user()
    {
        $userData = [
            'name' => 'Dr. Pedro Sánchez',
            'email' => 'pedro@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'medico',
            'department' => 'Cardiología',
            'specialties' => ['Cardiología', 'Medicina Interna'],
            'medical_license' => 'MP-12345',
            'phone' => '+57 300 123 4567',
            'is_active' => true
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Dr. Pedro Sánchez',
            'email' => 'pedro@test.com',
            'role' => 'medico'
        ]);

        // Verify password was hashed
        $user = User::where('email', 'pedro@test.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_admin_can_update_user()
    {
        $user = User::factory()->create([
            'role' => 'medico',
            'department' => 'Medicina Interna'
        ]);

        $updateData = [
            'name' => 'Dr. Updated Name',
            'department' => 'Cardiología',
            'specialties' => ['Cardiología', 'Hemodinamia']
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/users/{$user->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Dr. Updated Name',
            'department' => 'Cardiología'
        ]);
    }

    public function test_user_can_update_own_profile()
    {
        $updateData = [
            'name' => 'Updated Name',
            'phone' => '+57 301 234 5678'
        ];

        $response = $this->actingAs($this->medico)
            ->putJson("/api/users/{$this->medico->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->medico->id,
            'name' => 'Updated Name',
            'phone' => '+57 301 234 5678'
        ]);
    }

    public function test_user_cannot_update_other_user_profile()
    {
        $otherUser = User::factory()->create(['role' => 'medico']);

        $updateData = [
            'name' => 'Hacked Name'
        ];

        $response = $this->actingAs($this->medico)
            ->putJson("/api/users/{otherUser->id}", $updateData);

        $response->assertStatus(403);
    }

    public function test_admin_can_toggle_user_status()
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/users/{$user->id}/toggle-status");

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false
        ]);
    }

    public function test_admin_can_reset_user_password()
    {
        $user = User::factory()->create();

        $passwordData = [
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson("/api/users/{$user->id}/reset-password", $passwordData);

        $response->assertStatus(200);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_validation_errors_on_user_creation()
    {
        $invalidData = [
            'name' => '', // Required
            'email' => 'invalid-email', // Invalid format
            'password' => '123', // Too short
            'role' => 'invalid_role', // Invalid role
            'medical_license' => 'invalid', // Invalid format
            'phone' => 'invalid-phone' // Invalid format
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/users', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
                'role'
            ]);
    }

    public function test_cannot_create_duplicate_email()
    {
        $existingUser = User::factory()->create(['email' => 'existing@test.com']);

        $userData = [
            'name' => 'New User',
            'email' => 'existing@test.com', // Duplicate email
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'medico'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_cannot_delete_themselves()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/users/{$this->admin->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_other_users()
    {
        $user = User::factory()->create(['role' => 'medico']);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('users', [
            'id' => $user->id
        ]);
    }

    public function test_can_filter_users_by_role()
    {
        User::factory()->create(['role' => 'medico']);
        User::factory()->create(['role' => 'administrador']);

        $response = $this->actingAs($this->admin)
            ->get('/api/users?role=medico');

        $response->assertStatus(200);

        $data = $response->json('data.data');
        foreach ($data as $user) {
            $this->assertEquals('medico', $user['role']);
        }
    }

    public function test_can_search_users()
    {
        User::factory()->create(['name' => 'Dr. Juan Pérez']);
        User::factory()->create(['name' => 'Dr. María García']);

        $response = $this->actingAs($this->admin)
            ->get('/api/users?search=Juan');

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertStringContains('Juan', $data[0]['name']);
    }

    public function test_password_confirmation_required()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password', // Doesn't match
            'role' => 'medico'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
