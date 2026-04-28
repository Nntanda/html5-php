<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    private ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Generate member statement
     * GET /api/reports/member/{id}/statement
     */
    public function memberStatement(Request $request, string $id): JsonResponse
    {
        $member = Member::findOrFail($id);

        $startDate = $request->query('start_date') 
            ? Carbon::parse($request->query('start_date')) 
            : null;
        $endDate = $request->query('end_date') 
            ? Carbon::parse($request->query('end_date')) 
            : null;

        $statement = $this->reportService->generateMemberStatement($member, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $statement,
        ]);
    }

    /**
     * Generate member loan summary
     * GET /api/reports/member/{id}/loan-summary
     */
    public function memberLoanSummary(Request $request, string $id): JsonResponse
    {
        $member = Member::findOrFail($id);

        $startDate = $request->query('start_date') 
            ? Carbon::parse($request->query('start_date')) 
            : null;
        $endDate = $request->query('end_date') 
            ? Carbon::parse($request->query('end_date')) 
            : null;

        $summary = $this->reportService->generateMemberLoanSummary($member, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Generate SACCO savings summary
     * GET /api/reports/savings-summary
     */
    public function savingsSummary(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date') 
            ? Carbon::parse($request->query('start_date')) 
            : null;
        $endDate = $request->query('end_date') 
            ? Carbon::parse($request->query('end_date')) 
            : null;

        $summary = $this->reportService->generateSavingsSummary($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Generate SACCO loans summary
     * GET /api/reports/loans-summary
     */
    public function loansSummary(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date') 
            ? Carbon::parse($request->query('start_date')) 
            : null;
        $endDate = $request->query('end_date') 
            ? Carbon::parse($request->query('end_date')) 
            : null;

        $summary = $this->reportService->generateLoansSummary($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Generate transaction report
     * GET /api/reports/transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date') 
            ? Carbon::parse($request->query('start_date')) 
            : null;
        $endDate = $request->query('end_date') 
            ? Carbon::parse($request->query('end_date')) 
            : null;
        $type = $request->query('type'); // 'savings', 'loan', or null for all

        $report = $this->reportService->generateTransactionReport($startDate, $endDate, $type);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Generate overdue loans report
     * GET /api/reports/overdue-loans
     */
    public function overdueLoans(Request $request): JsonResponse
    {
        $asOfDate = $request->query('as_of_date') 
            ? Carbon::parse($request->query('as_of_date')) 
            : null;

        $report = $this->reportService->generateOverdueLoansReport($asOfDate);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }
}
