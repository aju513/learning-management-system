<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreditScore\ClaimCreditScoreRequest;
use App\Http\Requests\CreditScore\IndexCreditScoreRequest;
use App\Http\Requests\CreditScore\RefreshAttendanceRequest;
use App\Models\CreditAward;
use App\Services\CreditScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CreditScoreController extends Controller
{
    public function __construct(private readonly CreditScoreService $service) {}

    public function index(IndexCreditScoreRequest $request): View
    {
        return view('pages.admin.credit-scores.index', $this->service->pageData($request->user()) + ['title' => 'Credit Scores']);
    }

    public function claim(ClaimCreditScoreRequest $request, CreditAward $creditAward): RedirectResponse
    {
        $this->service->claim($creditAward, $request->user());

        return back()->with('success', 'Credit score claimed successfully.');
    }

    public function refreshAttendance(RefreshAttendanceRequest $request): RedirectResponse
    {
        $fiscalYear = app(\App\Repositories\Contracts\FiscalYearRepositoryInterface::class)->active();
        if (! $fiscalYear) {
            return back()->withErrors(['attendance' => 'There is no active fiscal year.']);
        }
        $snapshot = $this->service->refreshAttendance($fiscalYear, $request->user());

        return back()->with($snapshot->succeeded() ? 'success' : 'error', $snapshot->succeeded() ? 'Attendance refreshed.' : 'Attendance could not be refreshed.');
    }
}
