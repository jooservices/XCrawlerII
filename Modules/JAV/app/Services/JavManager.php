<?php

namespace Modules\JAV\Services;

use Illuminate\Support\Facades\Log;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;

class JavManager
{
    /**
     * Store or update a JAV item.
     *
     * @throws \Exception
     */
    public function store(Item $item, string $source): Jav
    {
        try {
            $data = [
                'item_id' => $item->id,
                'code' => $item->code,
                'title' => $item->title,
                'url' => $item->url,
                'image' => $item->image,
                'date' => $item->date,
                'size' => $item->size,
                'description' => $item->description,
                'download' => $item->download,
                'source' => $source,
            ];

            $jav = Jav::updateOrCreate(
                [
                    'code' => $item->code,
                    'source' => $source,
                ],
                $data
            );

            // Sync actors
            $actorIds = $item->actresses->map(
                fn(string $name) => Actor::firstOrCreate(['name' => $name])->id
            )->toArray();
            $jav->actors()->sync($actorIds);

            // Sync tags
            $tagIds = $item->tags->map(
                fn(string $name) => Tag::firstOrCreate(['name' => $name])->id
            )->toArray();
            $jav->tags()->sync($tagIds);

            // Force re-index to include actors and tags
            $jav->searchable();

            Log::info('JAV item stored successfully', [
                'code' => $item->code,
                'source' => $source,
                'id' => $jav->id,
            ]);

            return $jav;
        } catch (\Exception $e) {
            Log::error('Failed to store JAV item', [
                'code' => $item->code,
                'source' => $source,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
