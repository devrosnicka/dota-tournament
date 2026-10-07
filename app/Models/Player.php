<?php

namespace App\Models;

use App\Enums\PlayerStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PlayerFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nick
 * @property string|null $login_code
 * @property CarbonImmutable|null $login_code_expires_at
 * @property PlayerStatus $status
 * @property int|null $withdrawn_from_round
 * @property int|null $seed_rank
 * @property float|null $seed_score
 */
class Player extends Model implements AuthenticatableContract
{
    use Authenticatable;

    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    protected $fillable = [
        'nick',
    ];

    protected $hidden = [
        'login_code',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlayerStatus::class,
            'login_code_expires_at' => 'immutable_datetime',
            'withdrawn_from_round' => 'integer',
            'seed_rank' => 'integer',
            'seed_score' => 'float',
        ];
    }
}
