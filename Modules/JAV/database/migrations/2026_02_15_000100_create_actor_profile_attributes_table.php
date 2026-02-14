<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actor_profile_attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->constrained('actors')->cascadeOnDelete();
            $table->string('source');
            $table->string('kind');
            $table->string('value_string')->nullable();
            $table->decimal('value_number', 10, 2)->nullable();
            $table->date('value_date')->nullable();
            $table->string('value_label')->nullable();
            $table->text('raw_value')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['actor_id', 'source', 'kind']);
            $table->index(['actor_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actor_profile_attributes');
    }
};
