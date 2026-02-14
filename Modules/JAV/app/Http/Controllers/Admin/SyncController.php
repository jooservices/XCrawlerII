<?php

namespace Modules\JAV\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SyncController extends Controller
{
    public function index(): View
    {
        return view('jav::dashboard.admin.provider_sync');
    }

    public function indexVue(): InertiaResponse
    {
        return Inertia::render('Admin/ProviderSync');
    }

    public function syncProgress(): View
    {
        return view('jav::dashboard.sync_progress');
    }

    public function syncProgressVue(): InertiaResponse
    {
        return Inertia::render('Admin/SyncProgress');
    }

    public function quickSyncVue(): InertiaResponse
    {
        return Inertia::render('Admin/Sync');
    }
}
