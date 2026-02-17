<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawler_response_caches', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('type')->index();
            $table->text('url');
            $table->string('cache_key')->unique();
            $table->integer('status_code')->nullable();
            $table->longText('headers')->nullable();
            $table->longText('body')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawler_response_caches');
    }
};
