<?php

namespace App\Http\Requests\Learning;

class CompleteLearningMaterialRequest extends ShowLearningMaterialRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->user()->can('learning.complete');
    }
}
