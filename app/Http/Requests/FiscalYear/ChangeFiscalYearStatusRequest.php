<?php

namespace App\Http\Requests\FiscalYear;

use Illuminate\Foundation\Http\FormRequest;

class ChangeFiscalYearStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('fiscal-years.edit') ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', 'in:draft,active,closed']];
    }
}
