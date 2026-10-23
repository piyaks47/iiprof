<?php

use App\Support\IiprofContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * One-time import of the iiprof.com content (menus, pages, people, images …).
 * Replaces only the starter content of earlier versions; edits made in the admin are kept.
 * If it cannot run now, the seeder retries on the next deploy (IiprofContent::importOnce()).
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            IiprofContent::importOnce();
        } catch (Throwable $e) {
            Log::warning('iiprof.com content import postponed: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        // Content stays; nothing to undo.
    }
};
