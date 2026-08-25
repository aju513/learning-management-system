<?php

namespace App\Http\Requests\FiscalYear;

use Illuminate\Foundation\Http\FormRequest;

class StoreFiscalYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('fiscal-years.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'], 'starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'attendance_threshold_days' => ['required', 'integer', 'min:1', 'max:100000'], 'attendance_credit_points' => ['required', 'numeric', 'gt:0', 'max:100000'],
        ];
    }
}
