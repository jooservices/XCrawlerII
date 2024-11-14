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
        Schema::create('jav_movieables', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('movie_id');
            $table->foreign('movie_id')->references('id')->on('jav_movies');

            $table->unsignedBigInteger('jav_movieable_id');
            $table->string('jav_movieable_type');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jav_movieables');
    }
};
