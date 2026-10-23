<?php

use App\Support\DemoContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/** The sample events show the covers shipped in public/media/events (they no longer depend on uploaded files). */
return new class extends Migration
{
    public function up(): void
    {
        try {
            DemoContent::useBundledCovers();
        } catch (Throwable $e) {
            Log::warning('Bundled event covers postponed: '.$e->getMessage());
        }
    }

    public function down(): void {}
};
