<?php

namespace App\Http\Requests\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('enrollments.create') ?? false;
    }

    public function rules(): array
    {
        return ['course_id' => ['required', 'integer', 'exists:courses,id'], 'trainees' => ['required', 'array', 'min:1'], 'trainees.*' => ['integer', 'distinct', 'exists:users,id']];
    }
}
