<?php

namespace Modules\JAV\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Modules\JAV\Http\Requests\SearchQualityPreviewRequest;
use Modules\JAV\Http\Requests\SearchQualityPublishRequest;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;

class SearchQualityController extends Controller
{
    public function preview(SearchQualityPreviewRequest $request): JsonResponse
    {
        $entityType = $request->entityType();
        $identifier = $request->identifier();
        $identifierMode = $request->identifierMode();

        $model = $this->resolveModel($entityType, $identifier, $identifierMode);
        if ($model === null) {
            return response()->json([
                'message' => 'Record not found.',
            ], 404);
        }

        return response()->json([
            'entity' => $this->entityMeta($entityType, $model),
            'search_index' => $model->searchableAs(),
            'payload' => $model->toSearchableArray(),
            'quality' => $this->qualityChecks($entityType, $model),
            'related' => $this->relatedStats($entityType, $model),
            'previewed_at' => now()->toDateTimeString(),
        ]);
    }

    public function publish(SearchQualityPublishRequest $request): JsonResponse
    {
        $entityType = $request->entityType();
        $identifier = $request->identifier();
        $identifierMode = $request->identifierMode();
        $reindexRelated = $request->reindexRelated();

        $model = $this->resolveModel($entityType, $identifier, $identifierMode);
        if ($model === null) {
            return response()->json([
                'message' => 'Record not found.',
            ], 404);
        }

        $reindexed = [];
        $model->searchable();
        $reindexed[] = $this->entityMeta($entityType, $model);

        if ($reindexRelated) {
            if ($entityType === 'jav' && $model instanceof Jav) {
                $actors = $model->actors()->get();
                foreach ($actors as $actor) {
                    $actor->searchable();
                    $reindexed[] = $this->entityMeta('actor', $actor);
                }

                $tags = $model->tags()->get();
                foreach ($tags as $tag) {
                    $tag->searchable();
                    $reindexed[] = [
                        'type' => 'tag',
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                }
            }

            if ($entityType === 'actor' && $model instanceof Actor) {
                $javs = $model->javs()->get();
                foreach ($javs as $jav) {
                    $jav->searchable();
                    $reindexed[] = $this->entityMeta('jav', $jav);
                }
            }
        }

        return response()->json([
            'message' => 'Index publish completed.',
            'entity' => $this->entityMeta($entityType, $model),
            'reindexed_count' => count($reindexed),
            'reindexed' => $reindexed,
            'published_at' => now()->toDateTimeString(),
        ]);
    }

    private function resolveModel(string $entityType, string $identifier, string $identifierMode): ?Model
    {
        $isNumericIdentifier = ctype_digit($identifier);

        if ($entityType === 'jav') {
            return $this->resolveJav($identifier, $identifierMode, $isNumericIdentifier);
        }

        return $this->resolveActor($identifier, $identifierMode, $isNumericIdentifier);
    }

    private function resolveJav(string $identifier, string $identifierMode, bool $isNumericIdentifier): ?Jav
    {
        if ($identifierMode === 'id' || ($identifierMode === 'auto' && $isNumericIdentifier)) {
            return Jav::query()->find((int) $identifier);
        }

        return Jav::query()->where('uuid', $identifier)->first();
    }

    private function resolveActor(string $identifier, string $identifierMode, bool $isNumericIdentifier): ?Actor
    {
        if ($identifierMode === 'id' || ($identifierMode === 'auto' && $isNumericIdentifier)) {
            return Actor::query()->find((int) $identifier);
        }

        return Actor::query()->where('uuid', $identifier)->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function entityMeta(string $entityType, Model $model): array
    {
        if ($entityType === 'jav' && $model instanceof Jav) {
            return [
                'type' => 'jav',
                'id' => $model->id,
                'uuid' => $model->uuid,
                'title' => $model->title,
                'code' => $model->code,
            ];
        }

        if ($entityType === 'actor' && $model instanceof Actor) {
            return [
                'type' => 'actor',
                'id' => $model->id,
                'uuid' => $model->uuid,
                'name' => $model->name,
            ];
        }

        return [
            'type' => $entityType,
            'id' => $model->getKey(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function relatedStats(string $entityType, Model $model): array
    {
        if ($entityType === 'jav' && $model instanceof Jav) {
            return [
                'actors' => $model->actors()->count(),
                'tags' => $model->tags()->count(),
            ];
        }

        if ($entityType === 'actor' && $model instanceof Actor) {
            return [
                'javs' => $model->javs()->count(),
            ];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function qualityChecks(string $entityType, Model $model): array
    {
        $warnings = [];
        $score = 100;

        if ($entityType === 'jav' && $model instanceof Jav) {
            if ((string) $model->code === '') {
                $warnings[] = 'Missing code.';
                $score -= 15;
            }
            if ((string) $model->title === '') {
                $warnings[] = 'Missing title.';
                $score -= 20;
            }
            if ((string) $model->image === '') {
                $warnings[] = 'Missing image.';
                $score -= 10;
            }
            if ($model->date === null) {
                $warnings[] = 'Missing release date.';
                $score -= 10;
            }
            if ($model->actors()->count() === 0) {
                $warnings[] = 'No linked actors.';
                $score -= 15;
            }
            if ($model->tags()->count() === 0) {
                $warnings[] = 'No linked tags.';
                $score -= 10;
            }
        }

        if ($entityType === 'actor' && $model instanceof Actor) {
            if ((string) $model->name === '') {
                $warnings[] = 'Missing actor name.';
                $score -= 30;
            }
            if ((string) $model->xcity_cover === '' && (string) $model->xcity_id === '') {
                $warnings[] = 'Missing XCITY profile and cover.';
                $score -= 20;
            }
            if ($model->javs()->count() === 0) {
                $warnings[] = 'Actor has no linked videos.';
                $score -= 20;
            }
        }

        $score = max(0, $score);

        return [
            'status' => empty($warnings) ? 'healthy' : 'needs_attention',
            'score' => $score,
            'warnings' => $warnings,
        ];
    }
}
