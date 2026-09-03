<?php

namespace App\Modules\Trainee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyForTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->can('test-applications.create') && $this->route('assessment')->isAvailable());
    }

    public function rules(): array
    {
        return [];
    }
}
