<?php

namespace Modules\JAV\Http\Controllers\Users\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;

class SearchSuggestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $query = trim((string) $validated['q']);
        $limit = (int) ($validated['limit'] ?? 8);
        $bucketLimit = max(3, min(12, $limit));
        $like = '%'.addcslashes($query, '\\%_').'%';

        $movieSuggestions = Jav::query()
            ->where(function ($builder) use ($like): void {
                $builder->where('code', 'like', $like)
                    ->orWhere('title', 'like', $like);
            })
            ->orderByDesc('views')
            ->limit($bucketLimit)
            ->get(['id', 'uuid', 'code', 'title'])
            ->map(static function (Jav $jav): array {
                $code = trim((string) $jav->code);
                $title = trim((string) $jav->title);

                return [
                    'type' => 'movie',
                    'label' => trim($code.' '.$title),
                    'href' => route('jav.vue.movies.show', $jav->uuid),
                ];
            });

        $actorSuggestions = Actor::query()
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit($bucketLimit)
            ->get(['id', 'name'])
            ->map(static function (Actor $actor): array {
                $name = trim((string) $actor->name);

                return [
                    'type' => 'actor',
                    'label' => $name,
                    'href' => route('jav.vue.dashboard', ['actor' => $name]),
                ];
            });

        $tagSuggestions = Tag::query()
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit($bucketLimit)
            ->get(['id', 'name'])
            ->map(static function (Tag $tag): array {
                $name = trim((string) $tag->name);

                return [
                    'type' => 'tag',
                    'label' => $name,
                    'href' => route('jav.vue.dashboard', ['tag' => $name]),
                ];
            });

        $suggestions = collect()
            ->concat($movieSuggestions)
            ->concat($actorSuggestions)
            ->concat($tagSuggestions)
            ->take($limit)
            ->values();

        return response()->json([
            'query' => $query,
            'suggestions' => $suggestions,
        ]);
    }
}
