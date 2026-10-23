<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\SiteSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SiteSettings::schema() as $key => $field) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $field['default']]);
        }

        Cache::forget(SiteSettings::CACHE_KEY);
    }
}
