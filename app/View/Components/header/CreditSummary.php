<?php

namespace App\View\Components\header;

use App\Services\CreditScoreService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CreditSummary extends Component
{
    public array $summary;

    public function __construct(CreditScoreService $service)
    {
        $this->summary = $service->navbarSummary(auth()->user());
    }

    public function render(): View|Closure|string
    {
        return view('components.header.credit-summary');
    }
}
