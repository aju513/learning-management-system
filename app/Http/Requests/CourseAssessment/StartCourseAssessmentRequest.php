<?php

namespace App\Http\Requests\CourseAssessment;

use App\Enums\MaterialType;
use App\Http\Requests\Learning\ShowLearningMaterialRequest;

class StartCourseAssessmentRequest extends ShowLearningMaterialRequest
{
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $material = $this->route('learning_material');
        $material->loadMissing('courseAssessment');

        return $material->type === MaterialType::CourseAssessment && $material->courseAssessment !== null;
    }
}
