<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $service) {}

    public function edit(): View
    {
        return view('pages.admin.profile', ['title' => 'My Profile']);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $this->service->update($request->user(), $request->validated());

        return back()->with('success', 'Profile updated.');
    }
}
