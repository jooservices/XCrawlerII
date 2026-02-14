<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actor_profile_attributes', function (Blueprint $table): void {
            $table->text('value_string')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('actor_profile_attributes', function (Blueprint $table): void {
            $table->string('value_string')->nullable()->change();
        });
    }
};
