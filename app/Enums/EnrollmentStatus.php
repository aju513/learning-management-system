<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function grantsLearningAccess(): bool
    {
        return in_array($this, [self::Active, self::Completed], true);
    }
}
