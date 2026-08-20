<?php

namespace App\Http\Requests\FiscalYear;

class UpdateFiscalYearRequest extends StoreFiscalYearRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('fiscal-years.edit') ?? false;
    }
}
