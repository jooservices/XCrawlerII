<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('actors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('avatar')->nullable();
            $table->json('aliases')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birthplace')->nullable();
            $table->string('blood_type')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('weight')->nullable();
            $table->unsignedInteger('bust')->nullable();
            $table->unsignedInteger('waist')->nullable();
            $table->unsignedInteger('hip')->nullable();
            $table->string('cup_size')->nullable();
            $table->json('hobbies')->nullable();
            $table->json('skills')->nullable();
            $table->json('attributes')->nullable();
            $table->dateTime('crawled_at')->nullable();
            $table->dateTime('seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actors');
    }
};
