<?php

namespace App\Http\Requests\CreditScore;

use Illuminate\Foundation\Http\FormRequest;

class IndexCreditScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('credit-scores.view-own') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
