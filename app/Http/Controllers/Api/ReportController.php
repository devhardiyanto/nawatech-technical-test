<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReportSummaryRequest;
use App\Services\ReportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function summary(ReportSummaryRequest $request, ReportService $reportService): JsonResponse
    {
        return ApiResponse::success(
            code: 'REPORT_ORDERS_SUMMARY_FETCHED',
            message: 'Orders summary report retrieved successfully.',
            data: $reportService->ordersSummary($request->validated()),
            meta: [
                'cache_ttl_seconds' => (int) config('report.cache_ttl_seconds', 120),
            ],
        );
    }
}
