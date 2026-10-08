<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $player_id
 * @property int $captain_id
 * @property int $position
 */
class FinalRole extends Model
{
    protected $fillable = [
        'player_id',
        'captain_id',
        'position',
    ];
}
