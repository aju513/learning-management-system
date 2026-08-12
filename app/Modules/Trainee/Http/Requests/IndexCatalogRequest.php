<?php

namespace App\Modules\Trainee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('course-catalog.view') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'difficulty' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced'])],
        ];
    }
}
