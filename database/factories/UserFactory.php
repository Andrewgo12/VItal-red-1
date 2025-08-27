<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = ['medico', 'administrador'];
        $departments = [
            'Medicina General',
            'Cardiología',
            'Neurología',
            'Ortopedia',
            'Pediatría',
            'Ginecología',
            'Urología',
            'Oftalmología',
            'Dermatología',
            'Psiquiatría',
            'Medicina Interna',
            'Administración'
        ];

        $specialties = [
            'Medicina General',
            'Cardiología',
            'Neurología',
            'Ortopedia',
            'Pediatría',
            'Ginecología',
            'Urología',
            'Oftalmología',
            'Dermatología',
            'Psiquiatría',
            'Medicina Interna',
            'Cirugía General',
            'Anestesiología',
            'Radiología'
        ];

        $role = fake()->randomElement($roles);
        $department = fake()->randomElement($departments);

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => $role,
            'department' => $department,
            'phone' => fake()->optional(0.8)->phoneNumber(),
            'medical_license' => $role === 'medico' ? 'MP-' . fake()->numberBetween(10000, 99999) : null,
            'specialties' => $role === 'medico' ? fake()->randomElements($specialties, fake()->numberBetween(1, 3)) : null,
            'is_active' => fake()->boolean(90), // 90% chance of being active
            'last_login_at' => fake()->optional(0.7)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the user is a medico.
     */
    public function medico(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'medico',
            'medical_license' => 'MP-' . fake()->numberBetween(10000, 99999),
            'specialties' => fake()->randomElements([
                'Medicina General',
                'Cardiología',
                'Neurología',
                'Ortopedia',
                'Pediatría',
                'Ginecología',
                'Urología',
                'Oftalmología',
                'Dermatología',
                'Psiquiatría',
                'Medicina Interna'
            ], fake()->numberBetween(1, 3)),
        ]);
    }

    /**
     * Indicate that the user is an administrator.
     */
    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'administrador',
            'department' => 'Administración',
            'medical_license' => null,
            'specialties' => null,
        ]);
    }

    /**
     * Indicate that the user is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the user is a cardiologist.
     */
    public function cardiologist(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'medico',
            'department' => 'Cardiología',
            'specialties' => ['Cardiología'],
            'medical_license' => 'MP-' . fake()->numberBetween(10000, 99999),
        ]);
    }

    /**
     * Indicate that the user is a neurologist.
     */
    public function neurologist(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'medico',
            'department' => 'Neurología',
            'specialties' => ['Neurología'],
            'medical_license' => 'MP-' . fake()->numberBetween(10000, 99999),
        ]);
    }

    /**
     * Indicate that the user is a pediatrician.
     */
    public function pediatrician(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'medico',
            'department' => 'Pediatría',
            'specialties' => ['Pediatría'],
            'medical_license' => 'MP-' . fake()->numberBetween(10000, 99999),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
