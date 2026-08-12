<?php

namespace App\Http\Requests\User;

use App\Http\Requests\User\Concerns\AuthorizesManagedRole;
use Illuminate\Foundation\Http\FormRequest;

class ManageRoleAccountRequest extends FormRequest
{
    use AuthorizesManagedRole;

    public function authorize(): bool
    {
        return $this->canManageConfiguredRole() && $this->targetMatchesManagedRole();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
