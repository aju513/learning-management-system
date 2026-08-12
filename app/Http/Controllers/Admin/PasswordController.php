<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function __construct(private readonly ProfileService $service) {}

    public function edit(): View
    {
        return view('pages.admin.password', ['title' => 'Change Password']);
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->service->updatePassword($request->user(), $request->validated('password'));

        return redirect()->route('portal.home')->with('success', 'Password updated.');
    }
}
