<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Management / team profiles (Director Council, Governing Body …) and testimonials. Guarded so it can be re-run. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('team_members')) Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('group', 60)->index(); // director-council, governing-body, managing-committee, board-of-studies, advisory-council
            $table->string('name', 160);
            $table->string('role', 255)->nullable();
            $table->text('bio')->nullable();
            $table->string('photo', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('testimonials')) Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('role', 255)->nullable();
            $table->text('quote')->nullable();
            $table->string('photo', 500)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('team_members');
    }
};
