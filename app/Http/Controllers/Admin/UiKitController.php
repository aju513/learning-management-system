<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UiKitController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('ui-kit.view');

        return view('pages.admin.ui-kit', ['title' => 'UI Kit']);
    }
}
