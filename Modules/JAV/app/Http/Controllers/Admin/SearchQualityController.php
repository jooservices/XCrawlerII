<?php

namespace Modules\JAV\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SearchQualityController extends Controller
{
    public function index(): InertiaResponse
    {
        return $this->indexVue();
    }

    public function indexVue(): InertiaResponse
    {
        return Inertia::render('Admin/SearchQuality');
    }
}
