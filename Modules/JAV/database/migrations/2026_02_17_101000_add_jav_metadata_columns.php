<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jav', function (Blueprint $table) {
            $table->json('genres')->nullable()->after('description');
            $table->json('series')->nullable()->after('genres');
            $table->json('maker')->nullable()->after('series');
            $table->json('studio')->nullable()->after('maker');
            $table->json('producer')->nullable()->after('studio');
            $table->json('director')->nullable()->after('producer');
            $table->json('label')->nullable()->after('director');
            $table->json('tag')->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('jav', function (Blueprint $table) {
            $table->dropColumn(['genres', 'series', 'maker', 'studio', 'producer', 'director', 'label', 'tag']);
        });
    }
};
