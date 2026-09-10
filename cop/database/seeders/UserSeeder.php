<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $roles = [
            'superadmin',
            'broadcaster',
            'advertiser',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'api', // change to 'web' if using web guard
            ]);
        }

        $users = [
            [
                'id'                => (string) Str::uuid(),
                'name'              => 'John Kamau',
                'email'             => 'john.kamau@gmail.com',
                'phone'             => '+254711000002',
                'password'          => Hash::make('00000000'),
                'email_verified_at' => now(),
                'role'              => 'broadcaster',
                'remember_token'    => Str::random(10),
                'created_at'        => now(),
                'updated_at'        => now(),
                'rank'              => 1.0,
                'game_type'         => 'Football',
            ],
            [
                'id'                => (string) Str::uuid(),
                'name'              => 'Mary Wanjiku',
                'email'             => 'mary.wanjiku@gmail.com',
                'phone'             => '+254722000003',
                'password'          => Hash::make('00000000'),
                'email_verified_at' => now(),
                'role'              => 'advertiser',
                'remember_token'    => Str::random(10),
                'created_at'        => now(),
                'updated_at'        => now(),
                'game_type'        => null,
            ],
            [
                'id'                => (string) Str::uuid(),
                'name'              => 'Michael Karisa',
                'email'             => 'michaelkarisa49@gmail.com',
                'phone'             => '+254733000004',
                'password'          => Hash::make('00000000'),
                'email_verified_at' => null,
                'role'              => 'broadcaster',
                'remember_token'    => Str::random(10),
                'created_at'        => now(),
                'updated_at'        => now(),
                'rank'              => 3.1,
                'game_type'         => 'Football',
            ],
            [
                'id'                => (string) Str::uuid(),
                'name'              => 'Grace Achieng',
                'email'             => 'grace.achieng@gmail.com',
                'phone'             => '+254744000005',
                'password'          => Hash::make('00000000'),
                'email_verified_at' => now(),
                'role'              => 'advertiser',
                'remember_token'    => Str::random(10),
                'created_at'        => now(),
                'updated_at'        => now(),
                'game_type'         => null,
            ],
        ];

        foreach ($users as $userData) {

            $role = $userData['role'];
           // unset($userData['role']);

            $user = User::create($userData);

            $user->assignRole($role);
        }

        // Assign superadmin role to admin user
        $adminId = User::where(
            'email',
            env('ADMIN_EMAIL', 'admin@switch6.com')
        )->value('id');

        $superAdmin = User::find($adminId);

        if ($superAdmin) {
            $superAdmin->assignRole('superadmin');
        }
    }
}