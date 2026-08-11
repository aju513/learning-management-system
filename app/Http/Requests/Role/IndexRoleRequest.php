<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class IndexRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.manage') ?? false;
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:100']];
    }
}
