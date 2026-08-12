<?php

namespace App\Enums;

enum SystemRole: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Instructor = 'instructor';
    case Trainee = 'trainee';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Instructor => 'Instructor',
            self::Trainee => 'Trainee',
        };
    }

    public function portalPermission(): string
    {
        return "portals.{$this->value}.access";
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::SuperAdmin => 'super-admin.dashboard',
            self::Admin => 'admin.dashboard',
            self::Instructor => 'instructor.dashboard',
            self::Trainee => 'learning.dashboard',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
