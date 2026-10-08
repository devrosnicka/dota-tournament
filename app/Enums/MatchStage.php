<?php

namespace App\Enums;

enum MatchStage: string
{
    case Group = 'group';
    case Final = 'final';
    case Tiebreak = 'tiebreak';
}
