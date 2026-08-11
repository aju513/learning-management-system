<?php

namespace App\Http\Requests\Role;

use App\Support\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'not_in:super-admin', Rule::unique('roles')->where('guard_name', 'web')],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::names()->all())],
        ];
    }
}
