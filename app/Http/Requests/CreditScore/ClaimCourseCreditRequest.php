<?php

namespace App\Http\Requests\CreditScore;

use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;

class ClaimCourseCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enrollment = $this->route('enrollment');

        return ($this->user()?->can('credit-scores.claim-own') ?? false)
            && $enrollment instanceof Enrollment
            && (int) $enrollment->user_id === (int) $this->user()?->id;
    }

    public function rules(): array
    {
        return [];
    }
}
