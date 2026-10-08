<?php

namespace App\Tournament;

use App\Domain\Final\FinalDraft;
use App\Domain\Final\MapResult;
use App\Enums\DraftStage;
use App\Enums\Side;
use App\Models\Player;

/**
 * Final draft and series as page props, shared by the player, admin and TV
 * pages.
 */
final class FinalView
{
    public function __construct(private readonly FinalStage $final) {}

    /**
     * @return array<string, mixed>|null
     */
    public function state(?Player $viewer = null): ?array
    {
        $draft = $this->final->draft();

        if ($draft === null) {
            return null;
        }

        $nicks = Player::query()->whereIn('id', array_keys($draft->placement))->pluck('nick', 'id');
        $player = fn (int $id) => [
            'id' => $id,
            'nick' => (string) $nicks[$id],
            'placement' => $draft->placement[$id],
            'role' => $draft->roles[$id] ?? null,
        ];
        $series = $this->final->series();
        $sidePickHolder = $draft->firstPickSide()?->other();
        $mySide = $viewer === null ? null : $draft->sideOf($viewer->id);
        $stage = $draft->stage();

        return [
            'stage' => $stage->value,
            'advantage' => $draft->advantage?->value,
            'captains' => ['A' => $player($draft->captainA), 'B' => $player($draft->captainB)],
            'firstPickSide' => $draft->firstPickSide()?->value,
            'pickOrder' => $draft->advantage === null ? [] : array_map(fn (Side $side) => $side->value, $draft->pickOrder()),
            'picksMade' => count($draft->picks),
            'pickingSide' => $draft->pickingSide()?->value,
            'pool' => array_map($player, $this->byPlacement($draft, $draft->available())),
            'teams' => [
                'A' => array_map($player, $draft->roleOrder(Side::A)),
                'B' => array_map($player, $draft->roleOrder(Side::B)),
            ],
            'roleTurn' => ['A' => $draft->roleTurn(Side::A), 'B' => $draft->roleTurn(Side::B)],
            'freeRoles' => ['A' => $draft->freeRoles(Side::A), 'B' => $draft->freeRoles(Side::B)],
            'series' => [
                'maps' => array_map(fn (MapResult $map, int $index) => [
                    'number' => $index + 1,
                    'winner' => $map->winner->value,
                    'killsA' => $map->killsA,
                    'killsB' => $map->killsB,
                    'radiant' => $map->radiant?->value,
                    'firstPick' => $map->firstPick?->value,
                ], $series->maps, array_keys($series->maps)),
                'wins' => ['A' => $series->wins(Side::A), 'B' => $series->wins(Side::B)],
                'winner' => $series->winner()?->value,
                'nextMap' => $series->nextMap(),
                'chooser' => $sidePickHolder === null ? null : $series->chooser($sidePickHolder)?->value,
                'sidePickHolder' => $sidePickHolder?->value,
            ],
            'you' => [
                'side' => $mySide?->value,
                'canChooseAdvantage' => $viewer !== null && $stage === DraftStage::Advantage && $viewer->id === $draft->captainA,
                'canPick' => $viewer !== null && $draft->pickingSide() !== null && $draft->captain($draft->pickingSide()) === $viewer->id,
                'canChooseRole' => $viewer !== null && $mySide !== null && $draft->roleTurn($mySide) === $viewer->id,
            ],
        ];
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function byPlacement(FinalDraft $draft, array $ids): array
    {
        usort($ids, fn (int $a, int $b) => $draft->placement[$a] <=> $draft->placement[$b]);

        return $ids;
    }
}
