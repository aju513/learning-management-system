<?php

namespace App\Http\Requests\Result;

use Illuminate\Foundation\Http\FormRequest;

class ReviewAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attempt = $this->route('assessment_attempt');
        $user = $this->user();

        return (bool) ($user?->can('results.grade-any') || ($user?->can('results.grade-owned') && $attempt->assessment->created_by === $user->id));
    }

    public function rules(): array
    {
        return [
            'reviews' => ['required', 'array', 'min:1'],
            'reviews.*.marks' => ['required', 'numeric', 'min:0'],
            'reviews.*.feedback' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
