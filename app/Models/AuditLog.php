<?php

namespace App\Models;

use App\Enums\AuditActor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property AuditActor $actor
 * @property int|null $player_id
 * @property string $action
 * @property array<string, mixed>|null $payload
 * @property CarbonImmutable $created_at
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'audit_log';

    protected $fillable = [
        'actor',
        'player_id',
        'action',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'actor' => AuditActor::class,
            'payload' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
