<?php

namespace App\Modules\Trainee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexTestCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('test-catalog.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:assessment_categories,id'],
        ];
    }
}
