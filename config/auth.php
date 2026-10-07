<?php

use App\Models\Player;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Players are the only authenticatable model. They have no passwords: a
    | player is logged in right after registration or with a short-lived
    | login code. The admin is a session flag, not a user account.
    |
    */

    'defaults' => [
        'guard' => 'web',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'players',
        ],
    ],

    'providers' => [
        'players' => [
            'driver' => 'eloquent',
            'model' => Player::class,
        ],
    ],

];
