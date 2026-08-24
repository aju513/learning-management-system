<?php

namespace App\Http\Requests\CreditScore;

use Illuminate\Foundation\Http\FormRequest;

class ClaimCreditScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('credit-scores.claim-own') ?? false)
            && (int) $this->route('credit_award')?->user_id === (int) $this->user()?->id;
    }

    public function rules(): array
    {
        return [];
    }
}
