<?php

namespace App\Modules\Trainee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Modules\Trainee\Http\Requests\ApplyForTestRequest;
use App\Services\AssessmentApplicationService;
use Illuminate\Http\RedirectResponse;

class TestApplicationController extends Controller
{
    public function __construct(private readonly AssessmentApplicationService $service) {}

    public function store(ApplyForTestRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->service->apply($assessment, $request->user());

        return redirect()->route('learning.assessments.index')->with('success', 'Your test application was submitted for review.');
    }
}
