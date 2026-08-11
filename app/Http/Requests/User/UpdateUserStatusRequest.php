<?php

namespace App\Http\Requests\User;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.change-status') ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(UserStatus::class)]];
    }
}
