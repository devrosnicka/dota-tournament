<?php

namespace App\Models;

use App\Enums\MatchStage;
use App\Enums\Side;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A single game ("match" is a reserved word in PHP).
 *
 * @property int $id
 * @property MatchStage $stage
 * @property int|null $round_id
 * @property int|null $lobby
 * @property int|null $map_number
 * @property Side|null $winner
 * @property int|null $kills_a
 * @property int|null $kills_b
 * @property Side|null $radiant_side
 * @property Side|null $first_pick_side
 * @property int|null $reported_by
 * @property CarbonImmutable|null $reported_at
 * @property-read Round|null $round
 * @property-read Collection<int, Player> $players
 */
class GameMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'stage',
        'round_id',
        'lobby',
        'map_number',
        'winner',
        'kills_a',
        'kills_b',
        'radiant_side',
        'first_pick_side',
        'reported_by',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'stage' => MatchStage::class,
            'winner' => Side::class,
            'radiant_side' => Side::class,
            'first_pick_side' => Side::class,
            'lobby' => 'integer',
            'map_number' => 'integer',
            'kills_a' => 'integer',
            'kills_b' => 'integer',
            'reported_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Round, $this>
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    /**
     * @return BelongsToMany<Player, $this, Pivot, 'pivot'>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'match_players', 'match_id')->withPivot('side');
    }

    /**
     * Player ids on one side; needs the players relation loaded.
     *
     * @return list<int>
     */
    public function team(Side $side): array
    {
        return array_values($this->players
            ->filter(fn (Player $player) => $player->getRelationValue('pivot')?->getAttribute('side') === $side->value)
            ->map(fn (Player $player) => $player->id)
            ->all());
    }
}
