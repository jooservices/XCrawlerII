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
        Schema::create('queues', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pool_id');
            $table->foreign('pool_id')
                ->references('id')
                ->on('pools')
                ->onDelete('cascade');
            $table->string('job_class')->index();
            $table->string('state_code')->index();
            $table->dateTime('executed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
