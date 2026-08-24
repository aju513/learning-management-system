<?php

namespace App\Enums;

enum FiscalYearStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return __(ucfirst($this->value));
    }
}
