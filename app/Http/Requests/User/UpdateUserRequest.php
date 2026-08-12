<?php

namespace App\Http\Requests\User;

use App\Enums\UserStatus;
use App\Support\LmsRoleAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('users.edit') ?? false)
            && LmsRoleAccess::canManage($this->user(), $this->route('user'));
    }

    public function rules(): array
    {
        $roleRules = ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')];
        if (! $this->user()->hasRole('super-admin')) {
            $roleRules[] = Rule::in(LmsRoleAccess::assignableRoleNames($this->user())->all());
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => ['nullable', 'confirmed', Password::default()],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'roles' => $this->user()->can('users.assign-roles') ? ['required', 'array', 'min:1'] : ['prohibited'],
            'roles.*' => $roleRules,
        ];
    }
}
