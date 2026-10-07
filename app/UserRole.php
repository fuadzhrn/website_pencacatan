<?php

namespace App;

enum UserRole: string
{
    case Admin = 'admin';
    case Petugas = 'petugas';
    case Supervisor = 'supervisor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Petugas => 'Petugas',
            self::Supervisor => 'Supervisor',
        };
    }
}
