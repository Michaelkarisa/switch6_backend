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
        $adminId = User::where('email', env('ADMIN_EMAIL', 'admin@arena.com'))->value('id');


        $users = [
            [
                'id'                => (string) Str::uuid(),
                'name'              => 'John Kamau',
                'email'             => 'john.kamau@arena.com',
                'phone'             => '+254711000002',
                'camera'            => 1,
                'password'          => Hash::make('00000000'),
                'email_verified_at' => now(),
                'role'              => 'broadcaster',
                'remember_token'    => Str::random(10),
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'id'                => (string) Str::uuid(),
                'name'              => 'Mary Wanjiku',
                'email'             => 'mary.wanjiku@arena.com',
                'phone'             => '+254722000003',
                'camera'            => null,
                'password'          => Hash::make('00000000'),
                'email_verified_at' => now(),
                'role'              => 'advertiser',
                'remember_token'    => Str::random(10),
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'id'                => (string) Str::uuid(),
                'name'              => 'Michael Karisa',
                'email'             => 'michaelkarisa49@gmail.com',
                'phone'             => '+254733000004',
                'camera'            => 2,
                'password'          => Hash::make('00000000'),
                'email_verified_at' => null,
                'role'              => 'broadcaster',
                'remember_token'    => Str::random(10),
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'id'                => (string) Str::uuid(),
                'name'              => 'Grace Achieng',
                'email'             => 'grace.achieng@arena.com',
                'phone'             => '+254744000005',
                'camera'            => null,
                'password'          => Hash::make('00000000'),
                'email_verified_at' => now(),
                'role'              => 'advertiser',
                'remember_token'    => Str::random(10),
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ];

        DB::table('users')->insert($users);

        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin']);

        $superAdmin = User::find($adminId);

        if ($superAdmin) {
            $superAdmin->assignRole($superAdminRole);
        }
    }
}