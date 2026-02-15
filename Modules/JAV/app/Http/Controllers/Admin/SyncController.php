<?php

namespace Modules\JAV\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SyncController extends Controller
{
    public function index(): InertiaResponse
    {
        return $this->indexVue();
    }

    public function indexVue(): InertiaResponse
    {
        return Inertia::render('Admin/ProviderSync');
    }

    public function syncProgress(): InertiaResponse
    {
        return $this->syncProgressVue();
    }

    public function syncProgressVue(): InertiaResponse
    {
        return Inertia::render('Admin/SyncProgress');
    }
}
