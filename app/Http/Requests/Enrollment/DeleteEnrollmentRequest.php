<?php

namespace App\Http\Requests\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class DeleteEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('enrollments.delete') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
