<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

class IndexActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('activity-log.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'event' => ['nullable', 'string', 'max:100'],
            'actor' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
