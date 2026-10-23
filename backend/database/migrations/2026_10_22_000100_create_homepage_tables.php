<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Website content managed from the admin sidebar: homepage sections and their items,
 * announcements, navigation menu, footer links and enquiries from the website.
 * Every table is guarded so a half-finished run can simply be run again.
 */
return new class extends Migration
{
    public function up(): void
    {
        // One row per homepage block (hero, about, events …); order + on/off + texts.
        if (! Schema::hasTable('home_sections')) Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('name', 100);
            $table->string('eyebrow', 120)->nullable();
            $table->string('title', 255)->nullable();
            $table->string('title_accent', 255)->nullable();
            $table->string('subtitle', 500)->nullable();
            $table->text('body')->nullable();
            $table->string('button_text', 80)->nullable();
            $table->string('button_url', 500)->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('announcements')) Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('text', 500);
            $table->string('url', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('hero_slides')) Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('eyebrow', 120)->nullable();
            $table->string('title', 255);
            $table->string('subtitle', 500)->nullable();
            $table->string('image')->nullable();
            $table->string('button_text', 80)->nullable();
            $table->string('button_url', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('stats')) Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->string('value', 20);
            $table->string('label', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('highlights')) Schema::create('highlights', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('description', 500)->nullable();
            $table->string('icon', 40)->default('light-bulb');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('events')) Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('summary', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->date('starts_on')->nullable();
            $table->string('location', 255)->nullable();
            $table->string('button_text', 80)->nullable();
            $table->string('button_url', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('gallery_items')) Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->nullable();
            $table->string('image');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('menu_items')) Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('label', 120);
            $table->string('url', 500)->nullable();
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->boolean('new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('footer_columns')) Schema::create('footer_columns', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('footer_links')) Schema::create('footer_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('footer_column_id')->constrained()->cascadeOnDelete();
            $table->string('label', 120);
            $table->string('url', 500);
            $table->boolean('new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('enquiries')) Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 255);
            $table->string('phone', 40)->nullable();
            $table->string('subject', 160)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new')->index();
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // Access level per role and admin area (0 none, 1 view, 2 manage). Admins always have full access.
        if (! Schema::hasTable('role_permissions')) Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 20);
            $table->string('area', 60);
            $table->unsignedTinyInteger('level')->default(0);
            $table->timestamps();
            $table->unique(['role', 'area']);
        });
    }

    public function down(): void
    {
        foreach (['role_permissions', 'enquiries', 'footer_links', 'footer_columns', 'menu_items', 'gallery_items',
            'events', 'highlights', 'stats', 'hero_slides', 'announcements', 'home_sections'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
