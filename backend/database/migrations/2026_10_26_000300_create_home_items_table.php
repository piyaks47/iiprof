<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Items of the newer homepage blocks (feature tiles, impact numbers, membership benefits and categories,
 * programmes, registrations & partners, FAQs) — one table, one `section` per block.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('home_items')) {
            return;
        }

        Schema::create('home_items', function (Blueprint $table) {
            $table->id();
            $table->string('section', 40)->index();
            $table->string('title', 255);
            $table->string('subtitle', 255)->nullable();
            $table->string('value', 60)->nullable();
            $table->text('description')->nullable();
            $table->string('icon', 40)->nullable();
            $table->string('image')->nullable();
            $table->string('button_text', 80)->nullable();
            $table->string('button_url', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_items');
    }
};
