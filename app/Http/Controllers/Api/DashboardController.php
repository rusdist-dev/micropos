<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service)
    {
    }

    /** Ringkasan lengkap dashboard: stats + grafik. */
    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->service->all()]);
    }

    public function stats(): JsonResponse
    {
        return response()->json(['data' => $this->service->stats()]);
    }

    public function salesChart(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->salesChart($request->integer('days', 7))]);
    }

    public function topProducts(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->topProducts($request->integer('limit', 5))]);
    }
}
