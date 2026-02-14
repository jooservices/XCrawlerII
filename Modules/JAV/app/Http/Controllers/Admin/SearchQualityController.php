<?php

namespace Modules\JAV\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SearchQualityController extends Controller
{
    public function index(): View
    {
        return view('jav::dashboard.admin.search_quality');
    }

    public function indexVue(): InertiaResponse
    {
        return Inertia::render('Admin/SearchQuality');
    }
}
