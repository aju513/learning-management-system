<?php

namespace App\Http\Requests\Assessment;

class SubmitAttemptRequest extends ShowAttemptRequest
{
    public function rules(): array
    {
        return ['answers' => ['nullable', 'array'], 'answers.*' => ['nullable']];
    }
}
