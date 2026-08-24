<?php

namespace App\Services;

class LocaleService
{
    public function set(string $locale): void
    {
        session()->put('locale', $locale);
        app()->setLocale($locale);
    }
}
