<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jav_actor', function (Blueprint $table) {
            $table->foreignId('jav_id')->constrained('jav')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('actors')->cascadeOnDelete();
            $table->unique(['jav_id', 'actor_id']);
        });

        Schema::create('jav_tag', function (Blueprint $table) {
            $table->foreignId('jav_id')->constrained('jav')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->unique(['jav_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jav_tag');
        Schema::dropIfExists('jav_actor');
    }
};
