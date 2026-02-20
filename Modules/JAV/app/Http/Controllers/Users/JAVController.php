<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserJavHistory;
use Modules\JAV\Services\CurationReadService;
use Modules\JAV\Services\SearchService;

class JAVController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly CurationReadService $curationReadService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): InertiaResponse
    {
        return $this->indexResourceVue();
    }

    public function indexResourceVue(): InertiaResponse
    {
        $items = Jav::query()
            ->latest()
            ->paginate(20);

        return Inertia::render('Javs/Index', [
            'items' => $items,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): InertiaResponse
    {
        return $this->createResourceVue();
    }

    public function createResourceVue(): InertiaResponse
    {
        return Inertia::render('Javs/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show(int|string $id): InertiaResponse
    {
        $jav = Jav::query()->whereKey($id)->firstOrFail();

        return $this->showResourceVue($jav);
    }

    public function showResourceVue(Jav $jav): InertiaResponse
    {
        $jav->load(['actors', 'tags']);

        return Inertia::render('Javs/Show', [
            'item' => $jav,
        ]);
    }

    public function showVue(Jav $jav): InertiaResponse
    {
        // Track history if user is authenticated
        if (auth()->check()) {
            UserJavHistory::firstOrCreate([
                'user_id' => auth()->id(),
                'jav_id' => $jav->id,
                'action' => 'view',
            ], [
                'updated_at' => now(), // Touch timestamp if exists
            ]);
        }

        // Load relationships
        $jav->load(['actors', 'tags']);

        // Check if liked
        $isLiked = false;
        if (auth()->check()) {
            $isLiked = $jav->favorites()->where('user_id', auth()->id())->exists();
        }

        // Get related movies
        $relatedByActors = $this->searchService->getRelatedByActors($jav, 10);
        $relatedByTags = $this->searchService->getRelatedByTags($jav, 10);

        $this->curationReadService->decorateMoviesWithFeaturedState([$jav]);
        $this->curationReadService->decorateMoviesWithFeaturedState($relatedByActors);
        $this->curationReadService->decorateMoviesWithFeaturedState($relatedByTags);

        return Inertia::render('Movies/Show', [
            'jav' => $jav,
            'relatedByActors' => $relatedByActors,
            'relatedByTags' => $relatedByTags,
            'isLiked' => $isLiked,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int|string $id): InertiaResponse
    {
        $jav = Jav::query()->whereKey($id)->firstOrFail();

        return $this->editResourceVue($jav);
    }

    public function editResourceVue(Jav $jav): InertiaResponse
    {
        $jav->load(['actors', 'tags']);

        return Inertia::render('Javs/Edit', [
            'item' => $jav,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
