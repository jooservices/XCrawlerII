<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jav_movies', function (Blueprint $table) {
            $table->id();
            $table->uuid();

            $table->string('cover')->nullable();
            $table->string('title')->nullable();
            $table->string('dvd_id')->index();
            $table->float('size')->nullable();

            $table->json('gallery')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jav_movies');
    }
};
