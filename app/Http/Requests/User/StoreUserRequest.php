<?php

namespace App\Http\Requests\User;

use App\Enums\UserStatus;
use App\Support\LmsRoleAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.create') ?? false;
    }

    public function rules(): array
    {
        $roleRules = ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')];
        if (! $this->user()->hasRole('super-admin')) {
            $roleRules[] = Rule::in(LmsRoleAccess::assignableRoleNames($this->user())->all());
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::default()],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'roles' => $this->user()->can('users.assign-roles') ? ['required', 'array', 'min:1'] : ['prohibited'],
            'roles.*' => $roleRules,
        ];
    }
}
