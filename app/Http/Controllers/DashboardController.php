<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function stats(Request $request)
    {
        $result = $this->dashboardService->getDashboardStatsForUser($request->user());

        return response()->json([
            'status' => 'success',
            'role' => $result['role'],
            'data' => $result['data']
        ]);
    }
}
