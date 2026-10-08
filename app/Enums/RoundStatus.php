<?php

namespace App\Enums;

enum RoundStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Done = 'done';
}
