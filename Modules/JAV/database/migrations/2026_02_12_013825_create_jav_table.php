<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jav', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key (NOT from ItemAdapter)

            // Core fields from Item DTO
            $table->string('item_id')->nullable(); // from Item->id
            $table->string('code')->nullable()->index(); // from Item->code (uppercase)
            $table->string('title')->nullable();
            $table->string('url')->nullable();
            $table->string('image')->nullable();
            $table->dateTime('date')->nullable()->index();
            $table->float('size')->nullable(); // in GB
            $table->text('description')->nullable();

            // JSON fields for collections
            $table->json('tags')->nullable(); // Collection -> JSON
            $table->json('actresses')->nullable(); // Collection -> JSON

            // Additional fields
            $table->string('download')->nullable();
            $table->string('source')->index(); // 'onejav', '141jav', etc.

            // Timestamps
            $table->timestamps();

            // Unique constraint: same code from different sources = different records
            $table->unique(['code', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jav');
    }
};
