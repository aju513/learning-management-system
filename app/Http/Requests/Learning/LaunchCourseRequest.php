<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class LaunchCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enrollment = $this->route('enrollment');

        return (bool) ($this->user()?->can('learning.view')
            && $enrollment->user_id === $this->user()->id
            && $enrollment->status->grantsLearningAccess());
    }

    public function rules(): array
    {
        return [];
    }
}
