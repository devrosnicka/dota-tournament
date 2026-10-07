<?php

namespace App\Enums;

enum AuditActor: string
{
    case Admin = 'admin';
    case Player = 'player';
    case System = 'system';
}
