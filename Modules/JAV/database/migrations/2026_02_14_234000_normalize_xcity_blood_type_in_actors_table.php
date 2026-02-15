<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('actors')
            ->select(['id', 'xcity_blood_type'])
            ->whereNotNull('xcity_blood_type')
            ->orderBy('id')
            ->chunkById(200, function ($actors): void {
                foreach ($actors as $actor) {
                    $normalized = $this->normalizeBloodType($actor->xcity_blood_type);

                    DB::table('actors')
                        ->where('id', $actor->id)
                        ->update([
                            'xcity_blood_type' => $normalized,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Data normalization is not safely reversible.
    }

    private function normalizeBloodType(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim((string) preg_replace('/\s+/u', ' ', $value));

        if (preg_match('/^(A|B|O|AB)\s*Type$/i', $normalized, $matches)) {
            return strtoupper($matches[1]);
        }

        if (preg_match('/^(A|B|O|AB)$/i', $normalized, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }
};
