<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class IndexReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.view') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
