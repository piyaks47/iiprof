<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A featured image per menu item: shown in the drop-down and as the banner of pages under it without their own. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('menu_items', 'image')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->string('image')->nullable()->after('page_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('menu_items', 'image')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }
    }
};
