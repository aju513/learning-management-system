<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Result\IndexResultRequest;
use App\Http\Requests\Result\ShowResultRequest;
use App\Models\AssessmentAttempt;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function __construct(private readonly AssessmentRepositoryInterface $assessments) {}

    public function index(IndexResultRequest $request): View
    {
        return view('pages.admin.results.index', ['results' => $this->assessments->paginateResults($request->validated(), $request->user()), 'assessments' => $this->assessments->allForFilter(), 'title' => 'Results']);
    }

    public function show(ShowResultRequest $request, AssessmentAttempt $assessmentAttempt): View
    {
        return view('pages.admin.assessment-player.result', ['attempt' => $this->assessments->findAttemptForTaking($assessmentAttempt), 'title' => 'Assessment Result']);
    }
}
