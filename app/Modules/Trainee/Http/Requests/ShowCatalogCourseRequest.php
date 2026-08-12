<?php

namespace App\Modules\Trainee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowCatalogCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->can('course-catalog.view') && $this->route('course')->isPublished());
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
