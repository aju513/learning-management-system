<?php

namespace App\Http\Requests\CreditScore;

use Illuminate\Foundation\Http\FormRequest;

class RefreshAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('credit-scores.refresh-attendance') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
