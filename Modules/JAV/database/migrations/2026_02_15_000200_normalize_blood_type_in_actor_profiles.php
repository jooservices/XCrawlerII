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
                    DB::table('actors')
                        ->where('id', $actor->id)
                        ->update([
                            'xcity_blood_type' => $this->normalizeBloodType($actor->xcity_blood_type),
                            'updated_at' => now(),
                        ]);
                }
            });

        DB::table('actor_profile_attributes')
            ->select(['id', 'value_string', 'raw_value'])
            ->where('kind', 'blood_type')
            ->orderBy('id')
            ->chunkById(200, function ($attributes): void {
                foreach ($attributes as $attribute) {
                    DB::table('actor_profile_attributes')
                        ->where('id', $attribute->id)
                        ->update([
                            'value_string' => $this->normalizeBloodType($attribute->value_string),
                            'raw_value' => $attribute->raw_value,
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
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim((string) preg_replace('/\s+/u', ' ', $value));
        if ($normalized === '' || $normalized === '- Type' || $normalized === '-') {
            return null;
        }

        if (preg_match('/^(A|B|O|AB)\s*Type$/i', $normalized, $matches)) {
            return strtoupper($matches[1]);
        }

        if (preg_match('/^(A|B|O|AB)$/i', $normalized, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }
};
