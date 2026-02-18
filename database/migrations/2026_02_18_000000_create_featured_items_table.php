<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('featured_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_type'); // movie, actor, tag, etc.
            $table->unsignedBigInteger('item_id'); // FK to movies/actors/tags
            $table->string('group'); // recent, trending, top, staff_pick, etc.
            $table->integer('rank')->default(0); // ordering within group
            $table->boolean('is_active')->default(true);
            $table->timestamp('featured_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable(); // extra info
            $table->timestamps();
            $table->index(['item_type', 'item_id']);
            $table->index(['group', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_items');
    }
};
