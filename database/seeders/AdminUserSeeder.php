<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin user already exists
        $adminUser = User::where('email', 'admin@garb.com')->first();
        
        if (!$adminUser) {
            User::create([
                'name' => 'GARB Admin',
                'username' => 'admin',
                'email' => 'admin@garb.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('Admin user created successfully!');
            $this->command->info('Email: admin@garb.com');
            $this->command->info('Password: password123');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }
}
