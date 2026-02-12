<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jav', function (Blueprint $table) {
            $table->dropColumn(['tags', 'actresses']);
        });
    }

    public function down(): void
    {
        Schema::table('jav', function (Blueprint $table) {
            $table->json('tags')->nullable();
            $table->json('actresses')->nullable();
        });
    }
};
