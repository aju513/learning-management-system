<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResolvePortalRequest;
use App\Services\PortalService;
use Illuminate\Http\RedirectResponse;

class PortalController extends Controller
{
    public function __construct(private readonly PortalService $portals) {}

    public function __invoke(ResolvePortalRequest $request): RedirectResponse
    {
        return redirect()->route($this->portals->dashboardRouteFor($request->user()));
    }
}
