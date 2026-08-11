<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ShowUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.show') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
