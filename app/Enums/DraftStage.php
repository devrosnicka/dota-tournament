<?php

namespace App\Enums;

enum DraftStage: string
{
    case Advantage = 'advantage';
    case Picks = 'picks';
    case Roles = 'roles';
    case Done = 'done';
}
