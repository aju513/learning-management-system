<?php

namespace App\Http\Requests\Result;

use Illuminate\Foundation\Http\FormRequest;

class ShowResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attempt = $this->route('assessment_attempt');
        $user = $this->user();

        return (bool) ($user?->can('results.manage') && ($user->can('results.view-all') || ($user->can('results.view-owned') && (int) $attempt->assessment->created_by === (int) $user->id) || (int) $attempt->user_id === (int) $user->id));
    }

    public function rules(): array
    {
        return [];
    }
}
