<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportingMonitoringController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Document::class);

        return view('master.reporting-monitoring');
    }
}
