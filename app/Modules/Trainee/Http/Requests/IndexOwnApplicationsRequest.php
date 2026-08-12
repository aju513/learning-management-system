<?php

namespace App\Modules\Trainee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexOwnApplicationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('course-applications.view-own') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
