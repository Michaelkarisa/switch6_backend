<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
/**
 * Seeds the admin role + permissions and creates the first superadmin user.
 *
 * Run: php artisan db:seed --class=AdminSeeder
 *
 * On production set ADMIN_EMAIL / ADMIN_PASSWORD in .env before seeding,
 * then rotate ADMIN_PASSWORD immediately after first login.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // ── Permissions ────────────────────────────────────────────────────
        $permissions = [
            // Users
            'view users', 'edit users', 'suspend users', 'delete users', 'impersonate users',

            // Matches
            'view all matches', 'force delete matches', 'reassign matches',

            // Plans & Subscriptions
            'manage plans', 'grant subscriptions', 'revoke subscriptions', 'extend subscriptions',

            // Advertisements & Payments
            'manage advertisements', 'manage payments', 'view revenue',

            // System
            'view analytics', 'view audit logs', 'manage cache', 'manage queue',

            // Notifications
            'broadcast notifications',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        }

        // ── Roles ──────────────────────────────────────────────────────────

        // superadmin → all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'api']);
        $superAdmin->syncPermissions(Permission::all());

        // admin → all except impersonate, force-delete, cache/queue management
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $admin->syncPermissions(Permission::whereNotIn('name', [
            'impersonate users',
            'force delete matches',
            'manage cache',
            'manage queue',
        ])->get());

        // ── First superadmin user ──────────────────────────────────────────
        $email    = env('ADMIN_EMAIL',    'admin@switch6.com');
        $password = env('ADMIN_PASSWORD', '00000000');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => 'Admin User',
                'email'             => $email,
                'phone'             => '+254700000001',
                'password'          => Hash::make($password),
                'email_verified_at' => now(),
                'role'              => 'superadmin',
                'remember_token'    => Str::random(10),
                'created_at'        => now(),
                'updated_at'        => now(),
                'rank'              =>null,
            ]
        );

        $user->assignRole('superadmin');

        $this->command->info("Admin seeded. Email: {$email}");
        $this->command->warn('Change ADMIN_PASSWORD in .env and rotate after first login!');
    }
}
