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
        if (! Schema::hasTable('ratings') || ! Schema::hasTable('tags')) {
            return;
        }

        Schema::table('ratings', function (Blueprint $table) {
            if (! Schema::hasColumn('ratings', 'tag_id')) {
                $table->foreignId('tag_id')->nullable()->after('jav_id')->constrained('tags')->cascadeOnDelete();
                $table->unique(['user_id', 'tag_id']);
                $table->index(['tag_id', 'rating']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('ratings') || ! Schema::hasColumn('ratings', 'tag_id')) {
            return;
        }

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropUnique('ratings_user_id_tag_id_unique');
            $table->dropIndex('ratings_tag_id_rating_index');
            $table->dropConstrainedForeignId('tag_id');
        });
    }
};
