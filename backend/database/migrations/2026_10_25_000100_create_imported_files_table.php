<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Files copied from iiprof.com onto this server, with where they came from (so missing copies can be fetched again). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('imported_files')) {
            return;
        }

        Schema::create('imported_files', function (Blueprint $table) {
            $table->id();
            $table->string('path', 191)->unique();
            $table->char('source_hash', 32)->index();
            $table->text('source_url');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('bytes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imported_files');
    }
};
