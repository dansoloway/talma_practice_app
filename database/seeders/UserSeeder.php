<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user if it doesn't exist
        $adminEmail = env('ADMIN_EMAIL', 'admin@talma.digital');
        $adminPassword = env('ADMIN_PASSWORD', 'admin123');
        
        if (!User::where('email', $adminEmail)->exists()) {
            User::create([
                'name' => env('ADMIN_NAME', 'Admin User'),
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'is_active' => true,
            ]);
            
            $this->command->info("Created admin user: {$adminEmail}");
            $this->command->warn("Default password: {$adminPassword} - Please change this!");
        } else {
            $this->command->info("Admin user already exists: {$adminEmail}");
        }
    }
}
