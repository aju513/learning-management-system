<?php

namespace App\Http\Requests\FiscalYear;

use Illuminate\Foundation\Http\FormRequest;

class DeleteFiscalYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('fiscal-years.delete') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
