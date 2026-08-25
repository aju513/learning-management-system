<?php

namespace App\Http\Requests\CourseApplication;

use App\Enums\EnrollmentStatus;
use Illuminate\Foundation\Http\FormRequest;

class ReviewCourseApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('enrollment');
        $user = $this->user();

        return (bool) ($application->status === EnrollmentStatus::Pending
            && ($user?->can('course-applications.review-all')
                || ($user?->can('course-applications.review-owned') && (int) $application->course->instructor_id === (int) $user->id)));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['review_note' => ['nullable', 'string', 'max:1000']];
    }
}
