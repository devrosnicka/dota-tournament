<?php

namespace App\Models;

use App\Enums\RoundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $number
 * @property string $format
 * @property RoundStatus $status
 */
class Round extends Model
{
    protected $fillable = [
        'number',
        'format',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'status' => RoundStatus::class,
        ];
    }

    /**
     * @return HasMany<GameMatch, $this>
     */
    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class)->orderBy('lobby');
    }

    /**
     * @return BelongsToMany<Player, $this>
     */
    public function sitters(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'round_sits');
    }

    public function hasResults(): bool
    {
        return $this->matches()->whereNotNull('winner')->exists();
    }
}
