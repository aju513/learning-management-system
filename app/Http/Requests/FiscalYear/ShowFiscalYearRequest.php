<?php

namespace App\Http\Requests\FiscalYear;

use Illuminate\Foundation\Http\FormRequest;

class ShowFiscalYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('fiscal-years.show') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
