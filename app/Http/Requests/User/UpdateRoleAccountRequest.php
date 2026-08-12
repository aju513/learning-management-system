<?php

namespace App\Http\Requests\User;

use App\Enums\UserStatus;
use App\Http\Requests\User\Concerns\AuthorizesManagedRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateRoleAccountRequest extends FormRequest
{
    use AuthorizesManagedRole;

    public function authorize(): bool
    {
        return $this->canManageConfiguredRole()
            && $this->targetMatchesManagedRole()
            && ($this->user()?->can('users.edit') ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => ['nullable', 'confirmed', Password::default()],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }
}
