<?php

namespace App\Http\Requests\AssessmentApplication;

use App\Enums\AssessmentApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;

class ReviewTestApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('assessment_application');
        $user = $this->user();

        return (bool) ($application->status === AssessmentApplicationStatus::Pending
            && ($user?->can('test-applications.review-all')
                || ($user?->can('test-applications.review-owned') && (int) $application->assessment->created_by === (int) $user->id)));
    }

    public function rules(): array
    {
        return ['review_note' => ['nullable', 'string', 'max:1000']];
    }
}
