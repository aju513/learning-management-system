<?php

namespace App\Http\Requests\Enrollment;

use App\Enums\EnrollmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('enrollments.manage') ?? false;
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:100'], 'course_id' => ['nullable', 'integer', 'exists:courses,id'], 'status' => ['nullable', Rule::enum(EnrollmentStatus::class)]];
    }
}
