<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->string('xcity_birth_date')->nullable()->after('xcity_cover');
            $table->string('xcity_blood_type')->nullable()->after('xcity_birth_date');
            $table->string('xcity_city_of_birth')->nullable()->after('xcity_blood_type');
            $table->string('xcity_height')->nullable()->after('xcity_city_of_birth');
            $table->string('xcity_size')->nullable()->after('xcity_height');
            $table->text('xcity_hobby')->nullable()->after('xcity_size');
            $table->text('xcity_special_skill')->nullable()->after('xcity_hobby');
            $table->text('xcity_other')->nullable()->after('xcity_special_skill');
            $table->json('xcity_profile')->nullable()->after('xcity_other');
        });
    }

    public function down(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->dropColumn([
                'xcity_birth_date',
                'xcity_blood_type',
                'xcity_city_of_birth',
                'xcity_height',
                'xcity_size',
                'xcity_hobby',
                'xcity_special_skill',
                'xcity_other',
                'xcity_profile',
            ]);
        });
    }
};
