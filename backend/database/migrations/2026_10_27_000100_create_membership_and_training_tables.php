<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership and training content shown on the homepage (and the training pages):
 * the member directory, membership fees, training programmes and their scheduled batches.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('featured_members')) {
            Schema::create('featured_members', function (Blueprint $table) {
                $table->id();
                $table->string('category', 40)->index();
                $table->string('name', 160);
                $table->string('designation', 255)->nullable();
                $table->string('organisation', 255)->nullable();
                $table->string('city', 120)->nullable();
                $table->string('photo')->nullable();
                $table->string('url', 500)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_fees')) {
            Schema::create('membership_fees', function (Blueprint $table) {
                $table->id();
                $table->string('grade', 160);
                $table->string('code', 30)->nullable();
                $table->string('annual_fee', 60)->nullable();
                $table->string('lifetime_fee', 60)->nullable();
                $table->string('note', 255)->nullable();
                $table->string('button_url', 500)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('training_programmes')) {
            Schema::create('training_programmes', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->string('slug', 191)->unique();
                $table->string('type', 30)->default('open');
                $table->string('tagline', 255)->nullable();
                $table->string('summary', 600)->nullable();
                $table->text('description')->nullable();
                $table->text('audience')->nullable();
                $table->text('outcomes')->nullable();
                $table->text('modules')->nullable();
                $table->string('certificate', 500)->nullable();
                $table->string('duration', 120)->nullable();
                $table->string('format', 120)->nullable();
                $table->string('fee', 120)->nullable();
                $table->string('image')->nullable();
                $table->string('button_text', 80)->nullable();
                $table->string('button_url', 500)->nullable();
                $table->boolean('is_featured')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('training_batches')) {
            Schema::create('training_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_programme_id')->constrained('training_programmes')->cascadeOnDelete();
                $table->string('batch_code', 60)->nullable();
                $table->string('format', 30)->default('classroom');
                $table->string('location', 160)->nullable();
                $table->date('starts_on');
                $table->date('ends_on')->nullable();
                $table->string('schedule', 255)->nullable();
                $table->unsignedInteger('seats')->nullable();
                $table->string('register_url', 500)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['is_active', 'starts_on']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('training_batches');
        Schema::dropIfExists('training_programmes');
        Schema::dropIfExists('membership_fees');
        Schema::dropIfExists('featured_members');
    }
};
