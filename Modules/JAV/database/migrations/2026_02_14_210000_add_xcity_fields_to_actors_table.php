<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->string('xcity_id')->nullable()->unique()->after('name');
            $table->string('xcity_url')->nullable()->after('xcity_id');
            $table->string('xcity_cover')->nullable()->after('xcity_url');
            $table->timestamp('xcity_synced_at')->nullable()->after('xcity_cover');
        });
    }

    public function down(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->dropUnique(['xcity_id']);
            $table->dropColumn([
                'xcity_id',
                'xcity_url',
                'xcity_cover',
                'xcity_synced_at',
            ]);
        });
    }
};
