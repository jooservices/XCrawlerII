<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('actors')
            ->select(['id', 'xcity_birth_date'])
            ->whereNotNull('xcity_birth_date')
            ->orderBy('id')
            ->chunkById(200, function ($actors): void {
                foreach ($actors as $actor) {
                    $normalized = $this->normalizeBirthDate($actor->xcity_birth_date);

                    DB::table('actors')
                        ->where('id', $actor->id)
                        ->update([
                            'xcity_birth_date' => $normalized,
                            'updated_at' => now(),
                        ]);
                }
            });

        DB::statement('ALTER TABLE actors MODIFY xcity_birth_date DATE NULL AFTER xcity_cover');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE actors MODIFY xcity_birth_date VARCHAR(255) NULL AFTER xcity_cover');
    }

    private function normalizeBirthDate(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim((string) preg_replace('/\s+/u', ' ', $value));
        if ($normalized === '') {
            return null;
        }

        foreach (['Y-m-d', 'Y M d', 'Y F d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $normalized)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
};
