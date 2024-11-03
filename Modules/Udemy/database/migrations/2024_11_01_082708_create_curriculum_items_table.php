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
        Schema::create('curriculum_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('course_id')->index();
            $table->foreign('course_id')->references('id')->on('udemy_courses')->onDelete('cascade');
            $table->boolean('is_published');
            $table->string('title');
            $table->string('type')->nullable();
            $table->string('class')->index();

            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('asset_type')->nullable();
            $table->string('asset_filename')->nullable()->index();
            $table->boolean('asset_is_external')->nullable();
            $table->boolean('asset_status')->nullable();
            $table->unsignedInteger('asset_time_estimation')->nullable();
            $table->string('asset_title')->nullable();
            $table->string('asset_class')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_items');
    }
};
