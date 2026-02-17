<?php

namespace Modules\JAV\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Events\JavStored;
use Modules\JAV\Events\JavStoreFailed;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Support\CodeNormalizer;

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
            $normalizedCode = CodeNormalizer::normalize($item->code);
            $normalizedItemId = $item->id ?? CodeNormalizer::compactIdFromCode($normalizedCode);

            $data = [
                'item_id' => $normalizedItemId,
                'code' => $normalizedCode,
                'title' => $item->title,
                'url' => $item->url,
                'image' => $item->image,
                'date' => $item->date,
                'size' => $item->size,
                'description' => $item->description,
                'genres' => $item->genres->values()->all(),
                'series' => $item->series->values()->all(),
                'maker' => $item->maker->values()->all(),
                'studio' => $item->studio->values()->all(),
                'producer' => $item->producer->values()->all(),
                'director' => $item->director->values()->all(),
                'label' => $item->label->values()->all(),
                'tag' => $item->tag->values()->all(),
                'download' => $item->download,
                'source' => $source,
            ];

            $jav = Jav::updateOrCreate(
                [
                    'code' => $normalizedCode,
                    'source' => $source,
                ],
                $data
            );

            // Bulk upsert actors
            $actorNames = $item->actresses
                ->map(fn ($name) => trim($name))
                ->filter(fn ($name) => $name !== '')
                ->unique()
                ->values();
            $existingActors = Actor::whereIn('name', $actorNames)->pluck('id', 'name');
            $missingActors = $actorNames->diff($existingActors->keys());
            if ($missingActors->isNotEmpty()) {
                $now = now();
                $toInsert = $missingActors->map(fn ($name) => [
                    'uuid' => (string) Str::uuid(),
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();
                Actor::insertOrIgnore($toInsert);
            }
            $allActors = Actor::whereIn('name', $actorNames)->pluck('id', 'name');
            $jav->actors()->sync($allActors->values()->all());

            // Bulk upsert tags
            $tagNames = $item->tags
                ->map(fn ($name) => trim($name))
                ->filter(fn ($name) => $name !== '')
                ->unique()
                ->values();
            $existingTags = Tag::whereIn('name', $tagNames)->pluck('id', 'name');
            $missingTags = $tagNames->diff($existingTags->keys());
            if ($missingTags->isNotEmpty()) {
                $toInsert = $missingTags->map(fn ($name) => ['name' => $name])->all();
                Tag::insertOrIgnore($toInsert);
            }
            $allTags = Tag::whereIn('name', $tagNames)->pluck('id', 'name');
            $jav->tags()->sync($allTags->values()->all());

            // Force re-index to include actors and tags
            $jav->searchable();

            JavStored::dispatch(
                (int) $jav->id,
                (string) $normalizedCode,
                $source,
                count($allActors->values()->all()),
                count($allTags->values()->all()),
                (bool) $jav->wasRecentlyCreated
            );

            Log::info('JAV item stored successfully', [
                'code' => $item->code,
                'source' => $source,
                'id' => $jav->id,
            ]);

            return $jav;
        } catch (\Exception $e) {
            JavStoreFailed::dispatch($item->code, $source, $e->getMessage());

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
