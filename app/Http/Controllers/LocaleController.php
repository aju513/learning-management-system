<?php

namespace App\Http\Controllers;

use App\Http\Requests\Locale\UpdateLocaleRequest;
use App\Services\LocaleService;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function __invoke(UpdateLocaleRequest $request, LocaleService $service): RedirectResponse
    {
        $service->set($request->validated('locale'));

        return back();
    }
}
