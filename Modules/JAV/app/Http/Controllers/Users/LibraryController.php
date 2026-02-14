<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\JAV\Http\Requests\GetFavoritesRequest;
use Modules\JAV\Http\Requests\GetHistoryRequest;
use Modules\JAV\Http\Requests\GetRecommendationsRequest;
use Modules\JAV\Repositories\DashboardReadRepository;
use Modules\JAV\Services\DashboardPreferencesService;
use Modules\JAV\Services\RecommendationService;

class LibraryController extends Controller
{
    public function __construct(
        private readonly DashboardReadRepository $dashboardReadRepository,
        private readonly RecommendationService $recommendationService,
        private readonly DashboardPreferencesService $dashboardPreferencesService,
    ) {
    }

    public function history(GetHistoryRequest $request): View
    {
        $user = auth()->user();
        $history = $this->dashboardReadRepository->historyForUser((int) $user->id, 30);

        return view('jav::dashboard.history', compact('history'));
    }

    public function favorites(GetFavoritesRequest $request): View
    {
        $user = auth()->user();
        $favorites = $this->dashboardReadRepository->favoritesForUser((int) $user->id, 30);

        return view('jav::dashboard.favorites', compact('favorites'));
    }

    public function recommendations(GetRecommendationsRequest $request): View
    {
        $user = auth()->user();
        $recommendations = $this->recommendationService->getRecommendationsWithReasons($user, 30);
        $preferences = $this->dashboardPreferencesService->resolve($user);

        return view('jav::dashboard.recommendations', compact('recommendations', 'preferences'));
    }
}
