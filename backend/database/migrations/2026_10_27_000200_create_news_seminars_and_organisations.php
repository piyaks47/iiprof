<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** News & blogs, seminars, corporate / institutional members, and a photos-or-video link on events. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20)->default('news')->index();
                $table->string('title', 255);
                $table->string('slug', 191)->unique();
                $table->string('excerpt', 600)->nullable();
                $table->longText('body')->nullable();
                $table->string('image')->nullable();
                $table->string('author', 160)->nullable();
                $table->date('published_on')->nullable();
                $table->string('external_url', 500)->nullable();
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['is_active', 'published_on']);
            });
        }

        if (! Schema::hasTable('seminars')) {
            Schema::create('seminars', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->string('slug', 191)->unique();
                $table->string('summary', 600)->nullable();
                $table->longText('description')->nullable();
                $table->string('image')->nullable();
                $table->string('link', 500)->nullable();
                $table->string('button_text', 80)->nullable();
                $table->date('starts_on')->nullable();
                $table->string('location', 160)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('member_organisations')) {
            Schema::create('member_organisations', function (Blueprint $table) {
                $table->id();
                $table->string('kind', 20)->index();
                $table->string('name', 255);
                $table->string('category', 120)->nullable();
                $table->string('city', 120)->nullable();
                $table->string('logo')->nullable();
                $table->string('website', 500)->nullable();
                $table->string('description', 800)->nullable();
                $table->string('member_since', 20)->nullable();
                $table->string('grade', 30)->nullable();
                $table->boolean('is_featured')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('events', 'media_url')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('media_url', 500)->nullable()->after('button_url');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_organisations');
        Schema::dropIfExists('seminars');
        Schema::dropIfExists('posts');
        if (Schema::hasColumn('events', 'media_url')) {
            Schema::table('events', fn (Blueprint $table) => $table->dropColumn('media_url'));
        }
    }
};
