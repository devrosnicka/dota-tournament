<?php

namespace App\Tournament\Exceptions;

use RuntimeException;

final class PhaseTransitionBlocked extends RuntimeException
{
    /**
     * @param  list<string>  $reasons
     */
    public function __construct(public readonly array $reasons)
    {
        parent::__construct(implode(' ', $reasons));
    }
}
