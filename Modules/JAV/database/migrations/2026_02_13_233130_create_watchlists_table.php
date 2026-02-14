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
        // Only create watchlists table if jav table exists
        if (! Schema::hasTable('jav')) {
            return;
        }

        Schema::create('watchlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('jav_id')->constrained('jav')->onDelete('cascade');
            $table->enum('status', ['to_watch', 'watching', 'watched'])->default('to_watch');
            $table->timestamps();

            $table->unique(['user_id', 'jav_id']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watchlists');
    }
};
