<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class DeleteRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.delete') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
