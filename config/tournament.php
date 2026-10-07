<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Access
    |--------------------------------------------------------------------------
    |
    | Shared secrets known only to the participants. The app runs on a public
    | domain, so registration, the admin area and the TV view are gated by
    | these values from the environment.
    |
    */

    'registration_code' => env('REGISTRATION_CODE'),

    'admin_password' => env('ADMIN_PASSWORD'),

    'tv_key' => env('TV_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Schedule generator
    |--------------------------------------------------------------------------
    |
    | Penalty weights and search parameters of the team generator (SPEC §1.6).
    |
    */

    'generator' => [
        'weights' => [
            'teammate_repeat' => 10,
            'opponent_repeat' => 3,
            'top_seeds_together' => 50,
            'seed_imbalance' => 20,
        ],
        'seed_tolerance' => 2.0,
        'samples_per_round' => 5000,
        'restarts' => 20,
    ],

    'default_rounds' => 5,

];
