<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClubManagerSeeder extends Seeder
{
    public function run(): void
    {

    $clubs = DB::table('clubs')->pluck('id');
        $clubManagers = [
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Sammy Omollo',
                'phone'        => '070500000',
                'club_id'      => $clubs->random(),
                'role'         => 'coach',
                'nationality'  => 'Kenya',
                'profile_url'  => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Patrick Aussems',
                'phone'        => '070500001',
                'club_id'      => $clubs->random(),
                'role'         => 'coach',
                'nationality'  => 'Kenya',
                'profile_url'  => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Robert Matano',
                'phone'        => '070500002',
                'club_id'      => $clubs->random(),
                'role'         => 'coach',
                'nationality'  => 'Kenya',
                'profile_url'  => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Johnmark Makwatta',
                'phone'        => '070500003',
                'club_id'      => $clubs->random(),
                'role'         => 'coach',
                'nationality'  => 'Kenya',
                'profile_url'  => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Francis Kimanzi',
                'phone'        => '070500004',
                'club_id'      => $clubs->random(),
                'role'         => 'coach',
                'nationality'  => 'Kenya',
                'profile_url'  => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Benjamin Nyangweso',
                'phone'        => '070500005',
                'club_id'      => $clubs->random(),
                'role'         => 'coach',
                'nationality'  => 'Kenya',
                'profile_url'  => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ];

        DB::table('club_managers')->insert($clubManagers);
    }
}
