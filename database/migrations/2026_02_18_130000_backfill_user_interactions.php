<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_interactions')) {
            return;
        }

        if (Schema::hasTable('favorites')) {
            DB::table('favorites')
                ->orderBy('id')
                ->chunkById(1000, function ($rows): void {
                    $payload = [];
                    foreach ($rows as $row) {
                        $payload[] = [
                            'user_id' => (int) $row->user_id,
                            'item_id' => (int) $row->favoritable_id,
                            'item_type' => (string) $row->favoritable_type,
                            'action' => Interaction::ACTION_FAVORITE,
                            'value' => null,
                            'meta' => null,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ];
                    }

                    if ($payload !== []) {
                        DB::table('user_interactions')->upsert(
                            $payload,
                            ['user_id', 'item_id', 'item_type', 'action'],
                            ['updated_at']
                        );
                    }
                });
        }

        if (Schema::hasTable('ratings')) {
            DB::table('ratings')
                ->orderBy('id')
                ->chunkById(1000, function ($rows): void {
                    $payload = [];
                    foreach ($rows as $row) {
                        $meta = $row->review !== null ? ['review' => $row->review] : null;
                        $payload[] = [
                            'user_id' => (int) $row->user_id,
                            'item_id' => (int) $row->jav_id,
                            'item_type' => Interaction::morphTypeFor(Jav::class),
                            'action' => Interaction::ACTION_RATING,
                            'value' => (int) $row->rating,
                            'meta' => $meta === null ? null : json_encode($meta),
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ];
                    }

                    if ($payload !== []) {
                        DB::table('user_interactions')->upsert(
                            $payload,
                            ['user_id', 'item_id', 'item_type', 'action'],
                            ['value', 'meta', 'updated_at']
                        );
                    }
                });
        }
    }

    public function down(): void
    {
        // No-op: keep user_interactions data.
    }
};
