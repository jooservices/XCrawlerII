<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->string('reactable_type');
            $table->string('reactable_id');
            $table->string('reaction', 16);
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['reactable_type', 'reactable_id', 'reaction'], 'reactions_target_reaction_unique');
            $table->index(['reactable_type', 'reactable_id'], 'reactions_target_index');
            $table->index(['reaction', 'count'], 'reactions_reaction_count_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
