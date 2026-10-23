<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each step is guarded so a half-finished run (MySQL DDL is not transactional) can simply be re-run.
        // e.g. Lenders, Borrowers, Doctor, Philanthropist …
        if (! Schema::hasTable('user_types')) Schema::create('user_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('description', 500)->nullable();
            $table->string('icon', 40)->default('user');
            // Default dashboard feature image for members of this type (path on the public disk or "preset:<key>").
            $table->string('feature_image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // e.g. Lenders → Banks/NBFC, Microfinance, Pvt Equity …
        if (! Schema::hasTable('user_subtypes')) Schema::create('user_subtypes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_type_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_type_id', 'slug']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'user_type_id')) {
                $table->foreignId('user_type_id')->nullable()->after('role')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'user_subtype_id')) {
                $table->foreignId('user_subtype_id')->nullable()->after('user_type_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }
            // Own dashboard feature image (path on the public disk or "preset:<key>").
            if (! Schema::hasColumn('users', 'dashboard_image')) {
                $table->string('dashboard_image')->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_subtype_id');
            $table->dropConstrainedForeignId('user_type_id');
            $table->dropColumn(['phone', 'dashboard_image']);
        });
        Schema::dropIfExists('user_subtypes');
        Schema::dropIfExists('user_types');
    }
};
