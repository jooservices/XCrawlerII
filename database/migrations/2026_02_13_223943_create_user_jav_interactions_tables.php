<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_jav_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jav_id')->constrained('jav')->cascadeOnDelete();
            $table->enum('action', ['view', 'download']);
            $table->timestamps();

            $table->unique(['user_id', 'jav_id', 'action']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('favoritable'); // Adds favoritable_id and favoritable_type
            $table->timestamps();

            $table->unique(['user_id', 'favoritable_id', 'favoritable_type'], 'user_favorite_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('user_jav_history');
    }
};
