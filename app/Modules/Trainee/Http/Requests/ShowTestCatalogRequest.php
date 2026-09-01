<?php

namespace App\Modules\Trainee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowTestCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assessments.take') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
