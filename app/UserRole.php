<?php

namespace App;

enum UserRole: string
{
    case Admin = 'admin';
    case Petugas = 'petugas';
    case Supervisor = 'supervisor';

    public function dashboardRouteName(): string
    {
        return match ($this) {
            self::Petugas => 'mobile.dashboard',
            self::Admin, self::Supervisor => 'dashboard',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Petugas => 'Petugas',
            self::Supervisor => 'Supervisor',
        };
    }
}
