<?php

namespace App\Services;

use App\Enums\FiscalYearStatus;
use App\Models\FiscalYear;
use App\Models\User;
use App\Repositories\Contracts\FiscalYearRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FiscalYearService
{
    public function __construct(private readonly FiscalYearRepositoryInterface $fiscalYears) {}

    public function create(array $data, User $actor): FiscalYear
    {
        $this->validateDates($data);
        $data['status'] = FiscalYearStatus::Draft;
        $fiscalYear = $this->fiscalYears->create($data);
        activity('lms')->causedBy($actor)->performedOn($fiscalYear)->event('fiscal-year.created')->log('Fiscal year created');

        return $fiscalYear;
    }

    public function update(FiscalYear $fiscalYear, array $data, User $actor): FiscalYear
    {
        if ($fiscalYear->status !== FiscalYearStatus::Draft) {
            throw ValidationException::withMessages(['fiscal_year' => 'Only draft fiscal years can be edited.']);
        }
        $this->validateDates($data, $fiscalYear);
        $fiscalYear = $this->fiscalYears->update($fiscalYear, $data);
        activity('lms')->causedBy($actor)->performedOn($fiscalYear)->event('fiscal-year.updated')->log('Fiscal year updated');

        return $fiscalYear;
    }

    public function changeStatus(FiscalYear $fiscalYear, FiscalYearStatus $status, User $actor): FiscalYear
    {
        if ($status === FiscalYearStatus::Active) {
            if ($fiscalYear->status === FiscalYearStatus::Closed) {
                throw ValidationException::withMessages(['status' => 'Closed fiscal years cannot become active again.']);
            }
            if ($this->fiscalYears->active() && $this->fiscalYears->active()->id !== $fiscalYear->id) {
                throw ValidationException::withMessages(['status' => 'Close the current active fiscal year before activating another.']);
            }
            if ($fiscalYear->attendance_threshold_days < 1 || (float) $fiscalYear->attendance_credit_points <= 0) {
                throw ValidationException::withMessages(['status' => 'Set a positive attendance threshold and credit value before activating.']);
            }
        }
        if ($status === FiscalYearStatus::Closed && $fiscalYear->status !== FiscalYearStatus::Active) {
            throw ValidationException::withMessages(['status' => 'Only an active fiscal year can be closed.']);
        }
        if ($fiscalYear->status === FiscalYearStatus::Closed) {
            throw ValidationException::withMessages(['status' => 'Closed fiscal years cannot change state.']);
        }
        if ($status === FiscalYearStatus::Draft && $fiscalYear->status !== FiscalYearStatus::Draft) {
            throw ValidationException::withMessages(['status' => 'An active fiscal year cannot return to draft.']);
        }

        $fiscalYear = $this->fiscalYears->update($fiscalYear, ['status' => $status]);
        activity('lms')->causedBy($actor)->performedOn($fiscalYear)->event('fiscal-year.status-changed')
            ->withProperties(['status' => $status->value])->log('Fiscal year status changed');

        return $fiscalYear;
    }

    public function delete(FiscalYear $fiscalYear, User $actor): void
    {
        if ($fiscalYear->status !== FiscalYearStatus::Draft || $this->fiscalYears->hasRecords($fiscalYear)) {
            throw ValidationException::withMessages(['fiscal_year' => 'Only unused draft fiscal years can be deleted.']);
        }
        DB::transaction(function () use ($fiscalYear, $actor): void {
            activity('lms')->causedBy($actor)->performedOn($fiscalYear)->event('fiscal-year.deleted')->log('Fiscal year deleted');
            $this->fiscalYears->delete($fiscalYear);
        });
    }

    private function validateDates(array $data, ?FiscalYear $ignore = null): void
    {
        $startsOn = new \DateTimeImmutable((string) $data['starts_on']);
        $endsOn = new \DateTimeImmutable((string) $data['ends_on']);
        if ($endsOn < $startsOn) {
            throw ValidationException::withMessages(['ends_on' => 'The end date must be on or after the start date.']);
        }
        if ($this->fiscalYears->hasOverlap($startsOn, $endsOn, $ignore)) {
            throw ValidationException::withMessages(['starts_on' => 'This date range overlaps another fiscal year.']);
        }
    }
}
