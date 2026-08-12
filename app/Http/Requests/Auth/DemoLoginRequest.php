<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DemoLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('lms.demo_login.enabled');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'account' => [
                'required',
                'string',
                Rule::in(array_keys(config('lms.demo_login.accounts', []))),
            ],
        ];
    }
}
