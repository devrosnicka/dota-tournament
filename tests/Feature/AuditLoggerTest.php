<?php

use App\Enums\AuditActor;
use App\Models\AuditLog;
use App\Models\Player;
use App\Tournament\AuditLogger;

it('records who did what', function () {
    $player = Player::factory()->create();
    $logger = app(AuditLogger::class);

    $logger->admin('phase.changed', ['to' => 'ranking']);
    $logger->player($player, 'match.reported', ['match_id' => 7]);
    $logger->system('schedule.generated');

    $entries = AuditLog::query()->orderBy('id')->get();

    expect($entries)->toHaveCount(3)
        ->and($entries[0]->actor)->toBe(AuditActor::Admin)
        ->and($entries[0]->payload)->toBe(['to' => 'ranking'])
        ->and($entries[1]->actor)->toBe(AuditActor::Player)
        ->and($entries[1]->player_id)->toBe($player->id)
        ->and($entries[2]->actor)->toBe(AuditActor::System)
        ->and($entries[2]->payload)->toBeNull();
});
