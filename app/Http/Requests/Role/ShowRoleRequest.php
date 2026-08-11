<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class ShowRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.show') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
