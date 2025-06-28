<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء دور المدير
        $adminRole = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web'
        ]);

        // Check if admin user already exists
        $adminUser = User::where('email', 'admin@garb.com')->first();
        
        if (!$adminUser) {
            $adminUser = User::create([
                'name' => 'GARB Admin',
                'username' => 'admin',
                'email' => 'admin@garb.com',
                'password' => Hash::make('admin123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('Admin user created successfully!');
        } else {
            // تحديث كلمة المرور
            $adminUser->update([
                'password' => Hash::make('admin123'),
                'is_active' => true,
            ]);
            $this->command->info('Admin user already exists - password updated.');
        }

        // إعطاء دور المدير للمستخدم
        if (!$adminUser->hasRole('Super Admin')) {
            $adminUser->assignRole($adminRole);
        }

        $this->command->info('Email: admin@garb.com');
        $this->command->info('Password: admin123');
    }
}
