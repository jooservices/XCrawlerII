<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\JAV\Http\Requests\GetFavoritesRequest;
use Modules\JAV\Http\Requests\GetHistoryRequest;
use Modules\JAV\Http\Requests\GetRecommendationsRequest;
use Modules\JAV\Repositories\DashboardReadRepository;
use Modules\JAV\Services\RecommendationService;

class LibraryController extends Controller
{
    public function __construct(
        private readonly DashboardReadRepository $dashboardReadRepository,
        private readonly RecommendationService $recommendationService,
    ) {}

    public function history(GetHistoryRequest $request): InertiaResponse
    {
        $user = auth()->user();
        $history = $this->dashboardReadRepository->historyForUser((int) $user->id, 30);

        return Inertia::render('User/History', [
            'history' => $history,
        ]);
    }

    public function favorites(GetFavoritesRequest $request): InertiaResponse
    {
        $user = auth()->user();
        $favorites = $this->dashboardReadRepository->favoritesForUser((int) $user->id, 30);

        return Inertia::render('User/Favorites', [
            'favorites' => $favorites,
        ]);
    }

    public function recommendations(GetRecommendationsRequest $request): InertiaResponse
    {
        $user = auth()->user();
        $recommendations = $this->recommendationService->getRecommendationsWithReasons($user, 30);

        return Inertia::render('User/Recommendations', [
            'recommendations' => $recommendations,
        ]);
    }
}
