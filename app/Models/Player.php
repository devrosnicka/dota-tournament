<?php

namespace App\Models;

use App\Enums\PlayerStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PlayerFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Player extends Model implements AuthenticatableContract
{
    use Authenticatable;

    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    protected $fillable = [
        'nick',
    ];

    /**
     * The seeding is visible to the admin only (SPEC §1.2).
     */
    protected $hidden = [
        'login_code',
        'seed_rank',
        'seed_score',
    ];

    /**
     * Players have no password and no "remember me" token.
     */
    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * @param  Builder<Player>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', PlayerStatus::Active);
    }

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
