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
        Schema::create('udemy_courses', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->boolean('is_course_available_in_org')->nullable();
            $table->boolean('is_practice_test_course')->nullable();
            $table->boolean('is_private')->nullable();
            $table->boolean('is_published')->nullable();

            $table->string('published_title')->index()->nullable();
            $table->string('title')->index()->nullable();
            $table->string('url')->index()->nullable();

            $table->string('class')->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('udemy_courses');
    }
};
