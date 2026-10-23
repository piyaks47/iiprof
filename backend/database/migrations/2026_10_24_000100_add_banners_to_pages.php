<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every page gets an editable banner (image = featured_image): title, description and a button.
 * public_path keeps the address a page answers at, e.g. /industry/banks-nbfc for slug industry-banks-nbfc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            if (! Schema::hasColumn('pages', 'banner_title')) {
                $table->string('banner_title')->nullable()->after('featured_image');
            }
            if (! Schema::hasColumn('pages', 'banner_text')) {
                $table->text('banner_text')->nullable()->after('banner_title');
            }
            if (! Schema::hasColumn('pages', 'banner_button_text')) {
                $table->string('banner_button_text', 80)->nullable()->after('banner_text');
            }
            if (! Schema::hasColumn('pages', 'banner_button_url')) {
                $table->string('banner_button_url', 500)->nullable()->after('banner_button_text');
            }
            if (! Schema::hasColumn('pages', 'public_path')) {
                $table->string('public_path')->nullable()->after('slug')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['banner_title', 'banner_text', 'banner_button_text', 'banner_button_url', 'public_path']);
        });
    }
};
