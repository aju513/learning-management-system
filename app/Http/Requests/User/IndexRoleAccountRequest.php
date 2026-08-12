<?php

namespace App\Http\Requests\User;

use App\Enums\UserStatus;
use App\Http\Requests\User\Concerns\AuthorizesManagedRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRoleAccountRequest extends FormRequest
{
    use AuthorizesManagedRole;

    public function authorize(): bool
    {
        return $this->canManageConfiguredRole();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(UserStatus::class)],
        ];
    }
}
