<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoginSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = DB::table('users')->pluck('id')->values();

        $logins = [];
        foreach ($userIds as $userId) {
            // Each user gets 2 login records
            $logins[] = [
                'id'         => (string) Str::uuid(),
                'user_id'    => $userId,
                'login_at'   => now()->subDays(rand(5, 30)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $logins[] = [
                'id'         => (string) Str::uuid(),
                'user_id'    => $userId,
                'login_at'   => now()->subDays(rand(1, 4)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('logins')->insert($logins);
    }
}
