<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FiscalYearStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\FiscalYear\ChangeFiscalYearStatusRequest;
use App\Http\Requests\FiscalYear\DeleteFiscalYearRequest;
use App\Http\Requests\FiscalYear\EditFiscalYearRequest;
use App\Http\Requests\FiscalYear\IndexFiscalYearRequest;
use App\Http\Requests\FiscalYear\ShowFiscalYearRequest;
use App\Http\Requests\FiscalYear\StoreFiscalYearRequest;
use App\Http\Requests\FiscalYear\UpdateFiscalYearRequest;
use App\Models\FiscalYear;
use App\Repositories\Contracts\FiscalYearRepositoryInterface;
use App\Services\FiscalYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FiscalYearController extends Controller
{
    public function __construct(private readonly FiscalYearRepositoryInterface $fiscalYears, private readonly FiscalYearService $service) {}

    public function index(IndexFiscalYearRequest $request): View
    {
        return view('pages.admin.fiscal-years.index', ['fiscalYears' => $this->fiscalYears->paginate($request->validated()), 'title' => 'Fiscal Years']);
    }

    public function create(): View
    {
        return view('pages.admin.fiscal-years.create', ['fiscalYear' => new FiscalYear, 'title' => 'Create Fiscal Year']);
    }

    public function store(StoreFiscalYearRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('super-admin.fiscal-years.index')->with('success', 'Fiscal year created.');
    }

    public function edit(EditFiscalYearRequest $request, FiscalYear $fiscalYear): View
    {
        return view('pages.admin.fiscal-years.edit', ['fiscalYear' => $fiscalYear, 'title' => 'Edit Fiscal Year']);
    }

    public function show(ShowFiscalYearRequest $request, FiscalYear $fiscalYear): View
    {
        return view('pages.admin.fiscal-years.show', ['fiscalYear' => $fiscalYear, 'title' => $fiscalYear->name]);
    }

    public function update(UpdateFiscalYearRequest $request, FiscalYear $fiscalYear): RedirectResponse
    {
        $this->service->update($fiscalYear, $request->validated(), $request->user());

        return redirect()->route('super-admin.fiscal-years.index')->with('success', 'Fiscal year updated.');
    }

    public function status(ChangeFiscalYearStatusRequest $request, FiscalYear $fiscalYear): RedirectResponse
    {
        $this->service->changeStatus($fiscalYear, FiscalYearStatus::from($request->validated('status')), $request->user());

        return back()->with('success', 'Fiscal year status updated.');
    }

    public function destroy(DeleteFiscalYearRequest $request, FiscalYear $fiscalYear): RedirectResponse
    {
        $this->service->delete($fiscalYear, $request->user());

        return redirect()->route('super-admin.fiscal-years.index')->with('success', 'Fiscal year deleted.');
    }
}
