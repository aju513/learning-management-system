<?php

namespace App\Http\Requests\User;

use App\Http\Requests\User\Concerns\AuthorizesManagedRole;
use Illuminate\Foundation\Http\FormRequest;

class DeleteRoleAccountRequest extends FormRequest
{
    use AuthorizesManagedRole;

    public function authorize(): bool
    {
        return $this->canManageConfiguredRole()
            && $this->targetMatchesManagedRole()
            && ($this->user()?->can('users.delete') ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
