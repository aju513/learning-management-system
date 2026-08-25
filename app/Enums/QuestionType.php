<?php

namespace App\Enums;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case QuestionAnswer = 'question_answer';

    public function label(): string
    {
        return __(match ($this) {
            self::SingleChoice => 'Single choice',
            self::MultipleChoice => 'Multiple select',
            self::TrueFalse => 'Yes / no',
            self::QuestionAnswer => 'Question & answer',
        });
    }

    public function usesOptions(): bool
    {
        return $this !== self::QuestionAnswer;
    }
}
