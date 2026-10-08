<?php

namespace App\Enums;

enum Side: string
{
    case A = 'A';
    case B = 'B';

    public function other(): self
    {
        return $this === self::A ? self::B : self::A;
    }
}
