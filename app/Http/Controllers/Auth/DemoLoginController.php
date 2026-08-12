<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DemoLoginRequest;
use App\Services\DemoLoginService;
use Illuminate\Http\RedirectResponse;

class DemoLoginController extends Controller
{
    public function __construct(private readonly DemoLoginService $service) {}

    public function __invoke(DemoLoginRequest $request): RedirectResponse
    {
        $this->service->authenticate($request, $request->string('account')->toString());

        return redirect()->route('portal.home');
    }
}
