<?php

use App\Models\Setting;
use App\Support\IiprofContent;
use App\Support\SiteSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/** Pages (with editable banners) for every menu / footer address, and the iiprof.com default banner. */
return new class extends Migration
{
    public function up(): void
    {
        try {
            IiprofContent::ensureLinkPages();

            $banner = Setting::query()->firstOrCreate(['key' => 'default_banner']);
            if (blank($banner->value)) {
                $banner->update(['value' => IiprofContent::settings()['default_banner']]);
            }
            Cache::forget(SiteSettings::CACHE_KEY);
        } catch (Throwable $e) {
            Log::warning('Banner pages postponed: '.$e->getMessage());
        }
    }

    public function down(): void {}
};
