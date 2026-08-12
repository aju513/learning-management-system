<?php

namespace App\Modules\Trainee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyForCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->can('course-applications.create') && $this->route('course')->isPublished());
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
