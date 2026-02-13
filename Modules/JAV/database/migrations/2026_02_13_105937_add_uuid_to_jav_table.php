<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jav', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Generate UUIDs for existing records
        DB::table('jav')->whereNull('uuid')->chunkById(100, function ($javs) {
            foreach ($javs as $jav) {
                DB::table('jav')
                    ->where('id', $jav->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            }
        });

        // Make uuid non-nullable and add unique index
        Schema::table('jav', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jav', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
