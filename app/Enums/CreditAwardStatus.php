<?php

namespace App\Enums;

enum CreditAwardStatus: string
{
    case Eligible = 'eligible';
    case Claimed = 'claimed';

    public function label(): string
    {
        return $this === self::Eligible ? 'Ready to claim' : 'Claimed';
    }
}
