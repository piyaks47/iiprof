<?php

use App\Support\SiteSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Images still loaded from iiprof.com over http:// are blocked or flagged on this https site:
 * switch them to https:// (iiprof.com serves the same files there).
 */
return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'gallery_items' => ['image'], 'hero_slides' => ['image'], 'events' => ['image'], 'home_sections' => ['image'],
            'team_members' => ['photo'], 'testimonials' => ['photo'], 'pages' => ['featured_image', 'content'], 'settings' => ['value'],
        ];

        try {
            foreach ($columns as $table => $fields) {
                foreach ($fields as $field) {
                    if (! Schema::hasColumn($table, $field)) {
                        continue;
                    }
                    foreach (['http://iiprof.com/', 'http://www.iiprof.com/'] as $old) {
                        DB::table($table)->where($field, 'like', '%'.$old.'%')->update([
                            $field => DB::raw('REPLACE('.DB::getQueryGrammar()->wrap($field).", '{$old}', 'https://iiprof.com/')"),
                        ]);
                    }
                }
            }
            Cache::forget(SiteSettings::CACHE_KEY);
        } catch (Throwable $e) {
            Log::warning('Could not switch iiprof.com image links to https: '.$e->getMessage());
        }
    }

    public function down(): void {}
};
