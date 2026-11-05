<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create first admin account
        User::create([
            'name' => 'Admin E-Novaz',
            'username' => 'admin',
            'email' => 'admin@enovaz.local',
            'password' => Hash::make('Admin@123456'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Admin account created successfully!');
        $this->command->info('📧 Email: admin@enovaz.local');
        $this->command->info('👤 Username: admin');
        $this->command->info('🔑 Password: Admin@123456');
        $this->command->warn('⚠️  Please change the password after first login!');
    }
}
