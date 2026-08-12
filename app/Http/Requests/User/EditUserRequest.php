<?php

namespace App\Http\Requests\User;

use App\Support\LmsRoleAccess;
use Illuminate\Foundation\Http\FormRequest;

class EditUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->can('users.edit') && LmsRoleAccess::canManage($this->user(), $this->route('user')));
    }

    public function rules(): array
    {
        return [];
    }
}
