<?php

namespace Modules\JAV\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ProviderSyncController extends Controller
{
    public function index(): View
    {
        return view('jav::dashboard.admin.provider_sync');
    }

    public function dispatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'in:onejav,141jav,ffjav'],
            'type' => ['required', 'in:new,popular,daily,tags'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $source = (string) $validated['source'];
        $type = (string) $validated['type'];
        $date = $validated['date'] ?? null;

        $lockKey = sprintf('jav:sync:dispatch:%s:%s', $source, $type);
        if (!Cache::add($lockKey, 1, now()->addSeconds(20))) {
            return response()->json([
                'message' => 'A sync request for this provider/type is already being dispatched. Please wait a moment.',
            ], 429);
        }

        Cache::put('jav:sync:active', [
            'provider' => $source,
            'type' => $type,
            'started_at' => now()->toIso8601String(),
        ], now()->addHours(6));

        $payload = [
            'provider' => $source,
            '--type' => $type,
        ];

        if ($type === 'daily' && is_string($date) && $date !== '') {
            $payload['--date'] = $date;
        }

        Artisan::call('jav:sync', $payload);

        return response()->json([
            'message' => 'Provider sync request queued successfully.',
            'source' => $source,
            'type' => $type,
            'date' => $payload['--date'] ?? null,
        ]);
    }
}
