<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\IndexActivityRequest;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __construct(private readonly ActivityRepositoryInterface $activities) {}

    public function __invoke(IndexActivityRequest $request): View
    {
        return view('pages.admin.activity.index', ['activities' => $this->activities->paginate($request->validated()), 'title' => 'Activity Log']);
    }
}
