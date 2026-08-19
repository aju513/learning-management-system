<?php

namespace App\Http\Requests\CourseAssessment;

use App\Enums\CourseAssessmentQuestionType;
use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourseAssessment($this->route('course_assessment'));
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::enum(CourseAssessmentQuestionType::class)],
            'marks' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'options' => ['required', 'array', 'min:2', 'max:8'],
            'options.*' => ['required', 'string', 'max:1000', 'distinct'],
            'correct_options' => ['required', 'array', 'min:1'],
            'correct_options.*' => ['required', 'integer', 'distinct', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $options = $this->input('options', []);
            $correct = $this->input('correct_options', []);
            if (collect($correct)->contains(fn ($index) => ! array_key_exists((int) $index, $options))) {
                $validator->errors()->add('correct_options', 'Select only valid answer options.');
            }
            if ($this->input('type') === CourseAssessmentQuestionType::SingleChoice->value && count($correct) !== 1) {
                $validator->errors()->add('correct_options', 'Single-choice questions require exactly one correct answer.');
            }
        }];
    }
}
