<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code')->unique();
            $table->string('item_id')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->text('cover')->nullable();
            $table->text('trailer')->nullable();
            $table->json('gallery')->nullable();
            $table->boolean('is_censored')->nullable();
            $table->boolean('has_subtitles')->nullable();
            $table->json('subtitles')->nullable();
            $table->date('release_date')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->dateTime('crawled_at')->nullable();
            $table->dateTime('seen_at')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
