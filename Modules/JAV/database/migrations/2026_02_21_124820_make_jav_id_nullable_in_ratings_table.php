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
        if (! Schema::hasTable('ratings') || ! Schema::hasColumn('ratings', 'jav_id')) {
            return;
        }

        Schema::table('ratings', function (Blueprint $table) {
            // Drop existing unique constraint since it requires jav_id
            // We will replace it with a more flexible rule in the app logic,
            // or we could keep it and just make jav_id nullable.
            // Actually, uniqueness of (user_id, jav_id) is still valid for movie ratings.

            // To change the column, we might need to drop the foreign key first on some MySQL versions
            $table->dropForeign(['jav_id']);
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->foreignId('jav_id')->nullable()->change();

            // Re-add foreign key
            $table->foreign('jav_id')->references('id')->on('jav')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('ratings') || ! Schema::hasColumn('ratings', 'jav_id')) {
            return;
        }

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropForeign(['jav_id']);
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->foreignId('jav_id')->nullable(false)->change();
            $table->foreign('jav_id')->references('id')->on('jav')->onDelete('cascade');
        });
    }
};
