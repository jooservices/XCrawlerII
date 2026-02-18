<?php

namespace Modules\JAV\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\FeaturedItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;

class FeaturedItemsController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'type' => ['required', 'string', 'in:movie,actor,tag'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $query = trim((string) $validated['q']);
        $type = (string) $validated['type'];
        $limit = (int) ($validated['limit'] ?? 12);
        $like = '%'.addcslashes($query, '\\%_').'%';

        if ($type === 'movie') {
            $items = Jav::query()
                ->where(function ($builder) use ($like): void {
                    $builder->where('code', 'like', $like)
                        ->orWhere('title', 'like', $like);
                })
                ->orderByDesc('views')
                ->limit($limit)
                ->get(['id', 'uuid', 'code', 'title', 'image', 'views'])
                ->map(static function (Jav $jav): array {
                    $code = trim((string) $jav->code);
                    $title = trim((string) $jav->title);

                    return [
                        'id' => (int) $jav->id,
                        'type' => 'movie',
                        'label' => trim($code.' '.$title),
                        'title' => $title,
                        'code' => $code,
                        'image' => $jav->image,
                        'uuid' => $jav->uuid,
                        'views' => (int) $jav->views,
                    ];
                })
                ->values();

            return response()->json(['items' => $items]);
        }

        if ($type === 'actor') {
            $items = Actor::query()
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'uuid', 'name'])
                ->map(static function (Actor $actor): array {
                    $name = trim((string) $actor->name);

                    return [
                        'id' => (int) $actor->id,
                        'type' => 'actor',
                        'label' => $name,
                        'name' => $name,
                        'uuid' => $actor->uuid,
                    ];
                })
                ->values();

            return response()->json(['items' => $items]);
        }

        $items = Tag::query()
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name'])
            ->map(static function (Tag $tag): array {
                $name = trim((string) $tag->name);

                return [
                    'id' => (int) $tag->id,
                    'type' => 'tag',
                    'label' => $name,
                    'name' => $name,
                ];
            })
            ->values();

        return response()->json(['items' => $items]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_type' => ['required', 'string', 'in:movie,actor,tag'],
            'item_id' => ['required', 'integer'],
        ]);

        $items = FeaturedItem::query()
            ->where('item_type', $validated['item_type'])
            ->where('item_id', (int) $validated['item_id'])
            ->orderBy('group')
            ->orderBy('rank')
            ->get(['id', 'group', 'rank', 'is_active', 'featured_at', 'expires_at']);

        return response()->json(['items' => $items]);
    }

    public function index(Request $request): JsonResponse
    {
        $items = FeaturedItem::query()
            ->with('item')
            ->when($request->string('group')->toString(), fn ($q, $group) => $q->where('group', $group))
            ->when($request->string('item_type')->toString(), fn ($q, $type) => $q->where('item_type', $type))
            ->orderBy('group')
            ->orderBy('rank')
            ->orderByDesc('featured_at')
            ->get();

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_type' => 'required|string',
            'item_id' => 'required|integer',
            'group' => 'required|string',
            'rank' => 'nullable|integer',
            'is_active' => 'boolean',
            'featured_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        if (empty($data['featured_at'])) {
            $data['featured_at'] = now();
        }

        $item = FeaturedItem::create($data);

        return response()->json($item, 201);
    }

    public function update(Request $request, FeaturedItem $featuredItem): JsonResponse
    {
        $data = $request->validate([
            'item_type' => 'sometimes|string',
            'item_id' => 'sometimes|integer',
            'group' => 'sometimes|string',
            'rank' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
            'featured_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        $featuredItem->update($data);

        return response()->json($featuredItem);
    }

    public function destroy(FeaturedItem $featuredItem): JsonResponse
    {
        $featuredItem->delete();

        return response()->json(['success' => true]);
    }
}
