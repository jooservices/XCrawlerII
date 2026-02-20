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
        Schema::create('curated_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('item_type', 120);
            $table->unsignedBigInteger('item_id');
            $table->string('curation_type', 80);
            $table->integer('position')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['curation_type', 'item_type', 'item_id'], 'uniq_curated_type_item');
            $table->index(['curation_type', 'created_at'], 'idx_curated_type_created');
            $table->index(['item_type', 'item_id'], 'idx_curated_item_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curated_items');
    }
};
