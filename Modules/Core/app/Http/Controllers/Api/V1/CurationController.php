<?php

namespace Modules\Core\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Http\Requests\Api\V1\ListCurationsRequest;
use Modules\Core\Http\Requests\Api\V1\StoreCurationRequest;
use Modules\Core\Models\CuratedItem;

class CurationController extends Controller
{
    public function index(ListCurationsRequest $request): JsonResponse
    {
        $query = CuratedItem::query()->latest();

        if ($request->filled('curation_type')) {
            $query->where('curation_type', (string) $request->string('curation_type'));
        }

        if ($request->filled('item_type')) {
            $query->where('item_type', (string) $request->string('item_type'));
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', (int) $request->integer('item_id'));
        }

        if ($request->has('active')) {
            $active = (bool) $request->boolean('active');
            if ($active) {
                $query->where(function ($activeQuery): void {
                    $activeQuery->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })->where(function ($activeQuery): void {
                    $activeQuery->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                });
            }
        }

        $perPage = (int) $request->integer('per_page', 30);

        return response()->json($query->paginate($perPage));
    }

    public function store(StoreCurationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $modelClass = $this->resolveItemModelClass((string) $validated['item_type']);

        $item = $modelClass::query()->findOrFail((int) $validated['item_id']);
        $itemId = (int) $item->getKey();

        $curation = CuratedItem::query()->firstOrCreate(
            [
                'curation_type' => (string) $validated['curation_type'],
                'item_type' => (string) $validated['item_type'],
                'item_id' => $itemId,
            ],
            [
                'position' => $validated['position'] ?? null,
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
                'meta' => $validated['meta'] ?? null,
                'user_id' => (int) $request->user()->id,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $curation,
        ]);
    }

    public function destroy(CuratedItem $curation): JsonResponse
    {
        $curation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Curation removed.',
        ]);
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    private function resolveItemModelClass(string $itemType): string
    {
        $map = (array) config('core.curations.item_models', []);

        if (! isset($map[$itemType]) || ! is_string($map[$itemType])) {
            abort(422, 'Unsupported item_type.');
        }

        return $map[$itemType];
    }
}
