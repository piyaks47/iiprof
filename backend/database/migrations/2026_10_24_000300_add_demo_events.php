<?php

use App\Support\DemoContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/** Six sample events (placeholders, editable under Website → Events). Runs once. */
return new class extends Migration
{
    public function up(): void
    {
        try {
            DemoContent::addEventsOnce();
        } catch (Throwable $e) {
            Log::warning('Sample events postponed: '.$e->getMessage());
        }
    }

    public function down(): void {}
};
