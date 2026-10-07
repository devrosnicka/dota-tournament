<?php

namespace App\Tournament;

use App\Enums\AuditActor;
use App\Models\AuditLog;
use App\Models\Player;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function admin(string $action, array $payload = []): void
    {
        $this->write(AuditActor::Admin, null, $action, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function player(Player $player, string $action, array $payload = []): void
    {
        $this->write(AuditActor::Player, $player->id, $action, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function system(string $action, array $payload = []): void
    {
        $this->write(AuditActor::System, null, $action, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function write(AuditActor $actor, ?int $playerId, string $action, array $payload): void
    {
        AuditLog::query()->create([
            'actor' => $actor,
            'player_id' => $playerId,
            'action' => $action,
            'payload' => $payload === [] ? null : $payload,
        ]);
    }
}
