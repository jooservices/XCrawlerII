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
        Schema::create('udemy_my_courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('udemy_course_id')->index();
            $table->foreign('udemy_course_id')->references('id')->on('udemy_courses')->onDelete('cascade');

            $table->unsignedBigInteger('user_token_id')->index();
            $table->foreign('user_token_id')->references('id')->on('user_tokens')->onDelete('cascade');

            $table->unsignedSmallInteger('completion_ratio')->nullable();
            $table->dateTime('enrollment_time')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('udemy_my_courses');
    }
};
