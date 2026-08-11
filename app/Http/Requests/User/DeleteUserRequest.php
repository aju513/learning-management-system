<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class DeleteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.delete') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
