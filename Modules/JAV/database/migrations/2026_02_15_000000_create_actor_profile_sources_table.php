<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actor_profile_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->constrained('actors')->cascadeOnDelete();
            $table->string('source');
            $table->string('source_actor_id')->nullable();
            $table->string('source_url')->nullable();
            $table->string('source_cover')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['actor_id', 'source']);
            $table->unique(['source', 'source_actor_id']);
            $table->index(['actor_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actor_profile_sources');
    }
};
