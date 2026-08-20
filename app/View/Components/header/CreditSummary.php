<?php

namespace App\View\Components\header;

use App\Services\CreditScoreService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CreditSummary extends Component
{
    public function __construct(private readonly CreditScoreService $service) {}

    public function render(): View|Closure|string
    {
        return view('components.header.credit-summary', [
            'summary' => $this->service->navbarSummary(auth()->user()),
        ]);
    }
}
