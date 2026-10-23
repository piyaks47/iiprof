<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates the first administrator if none exists.
 * Credentials come from ADMIN_EMAIL / ADMIN_PASSWORD / ADMIN_NAME
 * (the deploy tool passes them in for the first install only — they are never written to the server).
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->where('role', Role::Admin)->exists()) {
            return;
        }

        $email = env('ADMIN_EMAIL') ?: 'admin@example.com';
        $password = env('ADMIN_PASSWORD') ?: Str::password(16);

        User::query()->updateOrCreate(['email' => $email], [
            'name' => env('ADMIN_NAME') ?: 'Administrator',
            'password' => $password,
            'role' => Role::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command?->info("Admin user: {$email}".(env('ADMIN_PASSWORD') ? '' : " / password: {$password}"));
    }
}
