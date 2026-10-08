<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $pick_number
 * @property int $captain_id
 * @property int $player_id
 */
class FinalPick extends Model
{
    protected $fillable = [
        'pick_number',
        'captain_id',
        'player_id',
    ];
}
