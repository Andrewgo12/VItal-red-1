<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::firstOrCreate(
            ['email' => 'admin@vitalred.com'],
            [
                'name' => 'Administrador Sistema',
                'password' => Hash::make('admin123'),
                'role' => 'administrador',
                'department' => 'Administración',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create demo medical user
        User::firstOrCreate(
            ['email' => 'medico@vitalred.com'],
            [
                'name' => 'Dr. Juan Pérez',
                'password' => Hash::make('medico123'),
                'role' => 'medico',
                'department' => 'Medicina Interna',
                'phone' => '+57 300 123 4567',
                'medical_license' => 'MP-12345',
                'specialties' => ['Medicina Interna', 'Cardiología'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create additional demo users
        $demoUsers = [
            [
                'name' => 'Dra. María González',
                'email' => 'maria.gonzalez@vitalred.com',
                'password' => Hash::make('demo123'),
                'role' => 'medico',
                'department' => 'Pediatría',
                'phone' => '+57 301 234 5678',
                'medical_license' => 'MP-23456',
                'specialties' => ['Pediatría', 'Neonatología'],
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dr. Carlos Rodríguez',
                'email' => 'carlos.rodriguez@vitalred.com',
                'password' => Hash::make('demo123'),
                'role' => 'medico',
                'department' => 'Neurología',
                'phone' => '+57 302 345 6789',
                'medical_license' => 'MP-34567',
                'specialties' => ['Neurología', 'Neurocirugía'],
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dra. Ana Martínez',
                'email' => 'ana.martinez@vitalred.com',
                'password' => Hash::make('demo123'),
                'role' => 'medico',
                'department' => 'Ginecología',
                'phone' => '+57 303 456 7890',
                'medical_license' => 'MP-45678',
                'specialties' => ['Ginecología', 'Obstetricia'],
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dr. Luis Herrera',
                'email' => 'luis.herrera@vitalred.com',
                'password' => Hash::make('demo123'),
                'role' => 'medico',
                'department' => 'Urgencias',
                'phone' => '+57 304 567 8901',
                'medical_license' => 'MP-56789',
                'specialties' => ['Medicina de Urgencias', 'Medicina Interna'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        ];

        foreach ($demoUsers as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Usuarios demo creados exitosamente:');
        $this->command->info('Admin: admin@vitalred.com / admin123');
        $this->command->info('Médico: medico@vitalred.com / medico123');
        $this->command->info('Otros médicos: demo123');
    }
}
