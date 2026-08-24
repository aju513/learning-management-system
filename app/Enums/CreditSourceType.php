<?php

namespace App\Enums;

enum CreditSourceType: string
{
    case Attendance = 'attendance';
    case CourseCompletion = 'course_completion';
    case AssessmentPass = 'assessment_pass';

    public function label(): string
    {
        return __(match ($this) {
            self::Attendance => 'Attendance',
            self::CourseCompletion => 'Course completion',
            self::AssessmentPass => 'Passed test',
        });
    }
}
