<?php

namespace App\Http\Requests\Learning;

class DownloadLearningMaterialRequest extends ShowLearningMaterialRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->user()->can('learning.download');
    }
}
