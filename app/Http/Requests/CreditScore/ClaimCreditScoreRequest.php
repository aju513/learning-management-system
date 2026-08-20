<?php

namespace App\Http\Requests\CreditScore;

use Illuminate\Foundation\Http\FormRequest;

class ClaimCreditScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('credit-scores.claim-own') ?? false)
            && $this->route('credit_award')?->user_id === $this->user()?->id;
    }

    public function rules(): array
    {
        return [];
    }
}
