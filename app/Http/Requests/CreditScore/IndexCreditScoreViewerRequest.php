<?php

namespace App\Http\Requests\CreditScore;

use Illuminate\Foundation\Http\FormRequest;

class IndexCreditScoreViewerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('credit-scores.view-all') ?? false;
    }

    public function rules(): array
    {
        return [
            'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
            'trainee_id' => ['nullable', 'integer', 'exists:users,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'tab' => ['nullable', 'in:overall,courses,quizzes'],
        ];
    }
}
