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
        // Get admin credentials from environment or use defaults
        $adminName = env('ADMIN_NAME', 'Admin E-Novaz');
        $adminUsername = env('ADMIN_USERNAME', 'admin');
        $adminEmail = env('ADMIN_EMAIL', 'admin@enovaz.local');
        $adminPassword = env('ADMIN_PASSWORD', 'Admin@123456');
        
        // Check if admin already exists
        $existingAdmin = User::where('email', $adminEmail)
            ->orWhere('username', $adminUsername)
            ->first();
        
        if ($existingAdmin) {
            $this->command->warn('⚠️  Admin account already exists!');
            $this->command->info('📧 Email: ' . $existingAdmin->email);
            $this->command->info('👤 Username: ' . $existingAdmin->username);
            $this->command->warn('Skipping admin creation...');
            return;
        }
        
        // Create first admin account
        User::create([
            'name' => $adminName,
            'username' => $adminUsername,
            'email' => $adminEmail,
            'password' => Hash::make($adminPassword),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Admin account created successfully!');
        $this->command->newLine();
        $this->command->info('📧 Email: ' . $adminEmail);
        $this->command->info('👤 Username: ' . $adminUsername);
        $this->command->info('🔑 Password: ' . $adminPassword);
        $this->command->newLine();
        $this->command->warn('⚠️  IMPORTANT: Change the password after first login!');
        $this->command->newLine();
        $this->command->info('💡 TIP: You can customize credentials in .env:');
        $this->command->line('   ADMIN_NAME="Your Name"');
        $this->command->line('   ADMIN_USERNAME="yourusername"');
        $this->command->line('   ADMIN_EMAIL="your@email.com"');
        $this->command->line('   ADMIN_PASSWORD="YourSecurePassword@123"');
    }
}
