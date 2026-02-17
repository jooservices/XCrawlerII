<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missav_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('item_id')->nullable()->index();
            $table->string('code')->nullable()->index();
            $table->text('title')->nullable();
            $table->string('url')->index();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missav_schedules');
    }
};
