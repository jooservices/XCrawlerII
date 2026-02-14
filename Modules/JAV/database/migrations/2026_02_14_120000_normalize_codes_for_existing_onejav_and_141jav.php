<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sources = ['onejav', '141jav'];
        $lastId = 0;

        while (true) {
            $rows = DB::table('jav')
                ->whereIn('source', $sources)
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit(200)
                ->get();

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $row) {
                $lastId = $row->id;
                $normalizedCode = $this->normalizeCode($row->code);

                if ($normalizedCode === null) {
                    continue;
                }

                $normalizedItemId = strtolower(str_replace('-', '', $normalizedCode));

                $target = DB::table('jav')
                    ->where('source', $row->source)
                    ->where('code', $normalizedCode)
                    ->where('id', '!=', $row->id)
                    ->orderBy('id')
                    ->first();

                if ($target !== null) {
                    $this->mergeIntoExisting((int) $row->id, (int) $target->id);

                    DB::table('jav')
                        ->where('id', $target->id)
                        ->update([
                            'item_id' => $target->item_id ?: $normalizedItemId,
                            'updated_at' => now(),
                        ]);

                    continue;
                }

                DB::table('jav')
                    ->where('id', $row->id)
                    ->update([
                        'code' => $normalizedCode,
                        'item_id' => $normalizedItemId,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // No-op: code normalization is intentionally irreversible.
    }

    private function mergeIntoExisting(int $fromJavId, int $toJavId): void
    {
        $actorIds = DB::table('jav_actor')->where('jav_id', $fromJavId)->pluck('actor_id');
        foreach ($actorIds as $actorId) {
            DB::table('jav_actor')->insertOrIgnore([
                'jav_id' => $toJavId,
                'actor_id' => $actorId,
            ]);
        }

        $tagIds = DB::table('jav_tag')->where('jav_id', $fromJavId)->pluck('tag_id');
        foreach ($tagIds as $tagId) {
            DB::table('jav_tag')->insertOrIgnore([
                'jav_id' => $toJavId,
                'tag_id' => $tagId,
            ]);
        }

        DB::table('jav')->where('id', $fromJavId)->delete();
    }

    private function normalizeCode(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $normalized = strtoupper($raw);
        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^A-Z0-9-]/', '', $normalized) ?? $normalized;

        if (str_starts_with($normalized, 'FC2PPV')) {
            $normalized = 'FC2-PPV'.substr($normalized, 6);
        }

        if (str_starts_with($normalized, 'FC2-PPV')) {
            $suffix = preg_replace('/[^0-9A-Z]/', '', substr($normalized, 8)) ?? '';

            return $suffix !== '' ? 'FC2-PPV'.$suffix : 'FC2-PPV';
        }

        if (preg_match('/^([A-Z]+)-?([0-9][0-9A-Z]*)$/', $normalized, $matches)) {
            return $matches[1].'-'.$matches[2];
        }

        return $normalized;
    }
};
