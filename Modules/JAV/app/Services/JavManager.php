<?php

namespace Modules\JAV\Services;

use Illuminate\Support\Facades\Log;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Models\Jav;

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
            // Transform Item DTO to database format
            $data = [
                'item_id' => $item->id,
                'code' => $item->code,
                'title' => $item->title,
                'url' => $item->url,
                'image' => $item->image,
                'date' => $item->date,
                'size' => $item->size,
                'description' => $item->description,
                'tags' => $item->tags->toArray(),
                'actresses' => $item->actresses->toArray(),
                'download' => $item->download,
                'source' => $source,
            ];

            // Use updateOrCreate to handle duplicates based on (code, source)
            $jav = Jav::updateOrCreate(
                [
                    'code' => $item->code,
                    'source' => $source,
                ],
                $data
            );

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
