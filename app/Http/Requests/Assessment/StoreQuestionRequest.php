<?php

namespace App\Http\Requests\Assessment;

use App\Enums\QuestionType;
use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditAssessment($this->route('assessment'), 'assessments.edit');
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:5000'], 'type' => ['required', Rule::enum(QuestionType::class)],
            'marks' => ['required', 'numeric', 'min:0.01', 'max:10000'], 'options' => ['required', 'array', 'min:2', 'max:8'],
            'options.*' => ['required', 'string', 'max:1000', 'distinct'], 'correct_options' => ['required', 'array', 'min:1'],
            'correct_options.*' => ['required', 'integer', 'distinct', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $correct = $this->input('correct_options', []);
            $options = $this->input('options', []);
            if (collect($correct)->contains(fn ($index) => ! array_key_exists((int) $index, $options))) {
                $validator->errors()->add('correct_options', 'Select a valid correct option.');
            }
            if ($this->input('type') !== QuestionType::MultipleChoice->value && count($correct) !== 1) {
                $validator->errors()->add('correct_options', 'This question type requires exactly one correct answer.');
            }
        }];
    }
}
