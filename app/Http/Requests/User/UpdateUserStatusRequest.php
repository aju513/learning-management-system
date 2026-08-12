<?php

namespace App\Http\Requests\User;

use App\Enums\UserStatus;
use App\Support\LmsRoleAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->can('users.change-status') && LmsRoleAccess::canManage($this->user(), $this->route('user')));
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(UserStatus::class)]];
    }
}
