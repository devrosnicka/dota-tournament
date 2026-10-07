<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validační hlášky
    |--------------------------------------------------------------------------
    |
    | Jen pravidla, která aplikace používá. Název pole se vkládá do uvozovek,
    | aby se nemusel skloňovat.
    |
    */

    'accepted' => 'Pole „:attribute“ musí být potvrzené.',
    'array' => 'Pole „:attribute“ musí být seznam.',
    'between' => [
        'array' => 'Pole „:attribute“ musí mít :min až :max položek.',
        'numeric' => 'Pole „:attribute“ musí být mezi :min a :max.',
        'string' => 'Pole „:attribute“ musí mít :min až :max znaků.',
    ],
    'boolean' => 'Pole „:attribute“ musí být ano, nebo ne.',
    'digits' => 'Pole „:attribute“ musí mít :digits číslic.',
    'distinct' => 'Pole „:attribute“ obsahuje duplicitní hodnotu.',
    'exists' => 'Vybraná hodnota pole „:attribute“ neexistuje.',
    'in' => 'Vybraná hodnota pole „:attribute“ není platná.',
    'integer' => 'Pole „:attribute“ musí být celé číslo.',
    'max' => [
        'array' => 'Pole „:attribute“ smí mít nejvýš :max položek.',
        'numeric' => 'Pole „:attribute“ smí být nejvýš :max.',
        'string' => 'Pole „:attribute“ smí mít nejvýš :max znaků.',
    ],
    'min' => [
        'array' => 'Pole „:attribute“ musí mít aspoň :min položek.',
        'numeric' => 'Pole „:attribute“ musí být aspoň :min.',
        'string' => 'Pole „:attribute“ musí mít aspoň :min znaků.',
    ],
    'not_in' => 'Vybraná hodnota pole „:attribute“ není platná.',
    'numeric' => 'Pole „:attribute“ musí být číslo.',
    'regex' => 'Pole „:attribute“ má neplatný formát.',
    'required' => 'Pole „:attribute“ je povinné.',
    'size' => [
        'array' => 'Pole „:attribute“ musí mít :size položek.',
        'numeric' => 'Pole „:attribute“ musí být :size.',
        'string' => 'Pole „:attribute“ musí mít :size znaků.',
    ],
    'string' => 'Pole „:attribute“ musí být text.',
    'unique' => 'Hodnota pole „:attribute“ už je obsazená.',

    'attributes' => [
        'nick' => 'přezdívka',
        'code' => 'kód',
        'registration_code' => 'registrační kód',
        'password' => 'heslo',
    ],

];
