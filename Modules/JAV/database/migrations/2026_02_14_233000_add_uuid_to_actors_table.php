<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('actors')
            ->select(['id'])
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunkById(200, function ($actors): void {
                foreach ($actors as $actor) {
                    DB::table('actors')
                        ->where('id', $actor->id)
                        ->update([
                            'uuid' => (string) Str::uuid(),
                            'updated_at' => now(),
                        ]);
                }
            });

        DB::statement('ALTER TABLE actors MODIFY uuid CHAR(36) NOT NULL');

        Schema::table('actors', function (Blueprint $table) {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
