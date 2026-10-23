<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Safe to run on every deploy: each seeder only creates what is missing.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SettingsSeeder::class,
            PageSeeder::class,
            UserTypeSeeder::class,
            ModuleSeeder::class,
            HomepageSeeder::class,
        ]);
    }
}
