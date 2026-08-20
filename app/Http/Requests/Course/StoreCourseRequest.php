<?php

namespace App\Http\Requests\Course;

use App\Enums\NavigationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('courses.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'], 'short_description' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'], 'category_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'instructor_id' => $this->user()->can('courses.edit-any') ? ['nullable', 'integer', 'exists:users,id'] : ['prohibited'],
            'difficulty' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'estimated_duration_minutes' => ['required', 'integer', 'min:0', 'max:100000'],
            'credit_points' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'navigation_mode' => ['required', Rule::enum(NavigationMode::class)],
            'thumbnail' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
