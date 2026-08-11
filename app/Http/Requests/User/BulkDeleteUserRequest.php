<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkDeleteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.delete') ?? false;
    }

    public function rules(): array
    {
        return [
            'users' => ['required', 'array', 'min:1'],
            'users.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
        ];
    }
}
