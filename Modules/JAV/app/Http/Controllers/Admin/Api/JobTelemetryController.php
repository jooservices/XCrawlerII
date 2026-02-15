<?php

namespace Modules\JAV\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\JAV\Http\Requests\JobTelemetrySummaryRequest;
use Modules\JAV\Services\JobTelemetryAnalyticsService;

class JobTelemetryController extends Controller
{
    public function __construct(private readonly JobTelemetryAnalyticsService $analyticsService) {}

    public function summary(JobTelemetrySummaryRequest $request): JsonResponse
    {
        return response()->json($this->analyticsService->summary($request->validated()));
    }
}
