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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->uuid();

            $table->unsignedBigInteger('server_id');
            $table->foreign('server_id')->references('id')->on('servers');

            $table->string('path')->index();
            $table->string('filename')->index();

            $table->string('mime_type')->nullable();
            $table->string('encoder')->nullable();
            $table->string('extension')->nullable();
            $table->string('size')->nullable();

            $table->float('frame_rate')->nullable();

            $table->integer('width')->nullable();
            $table->integer('height')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
