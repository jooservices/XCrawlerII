<?php

namespace Modules\JAV\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class JobTelemetryController extends Controller
{
    public function indexVue(): InertiaResponse
    {
        return Inertia::render('Admin/JobTelemetry');
    }
}
