<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Feature areas a user can be given (Hunar, Jeevika, Khazana, Health …).
        // Guarded so a half-finished run can be re-run.
        if (! Schema::hasTable('modules')) Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->string('icon', 40)->default('puzzle');
            // Roles that get this module automatically, e.g. ["admin","editor","user"].
            // Text (not JSON) so older MySQL / MariaDB versions on shared hosting work too.
            $table->text('roles')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Modules every user of a given user type gets.
        if (! Schema::hasTable('module_user_type')) Schema::create('module_user_type', function (Blueprint $table) {
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['module_id', 'user_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_user_type');
        Schema::dropIfExists('modules');
    }
};
