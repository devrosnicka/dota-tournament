<?php

namespace App\Domain\Schedule;

/**
 * Penalty of one round's split into matches and teams (SPEC §1.6). Teams are
 * balanced only softly: the goal is to avoid one-sided games, not to make
 * every game a coin flip.
 */
final readonly class TeamCost
{
    /** @var array<int, true> */
    private array $topSeeds;

    /**
     * @param  array<int, int>  $seedPositions  player id => position among the active players (1 = strongest)
     */
    public function __construct(
        private GeneratorConfig $config,
        private array $seedPositions,
    ) {
        asort($seedPositions);
        $this->topSeeds = array_fill_keys(array_slice(array_keys($seedPositions), 0, 3), true);
    }

    /**
     * @param  list<MatchPlan>  $matches
     */
    public function cost(array $matches, History $history): float
    {
        $cost = 0.0;

        foreach ($matches as $match) {
            foreach ([$match->teamA, $match->teamB] as $team) {
                $cost += $this->teamCost($team, $history);
            }

            foreach ($match->teamA as $a) {
                foreach ($match->teamB as $b) {
                    $cost += $this->config->opponentRepeat * $history->opponents($a, $b);
                }
            }

            $over = abs($this->averageSeed($match->teamA) - $this->averageSeed($match->teamB)) - $this->config->seedTolerance;

            if ($over > 0) {
                $cost += $this->config->seedImbalance * $over ** 2;
            }
        }

        return $cost;
    }

    /**
     * @param  list<int>  $team
     */
    public function averageSeed(array $team): float
    {
        $sum = 0;

        foreach ($team as $player) {
            $sum += $this->seedPositions[$player];
        }

        return $sum / count($team);
    }

    /**
     * @param  list<int>  $team
     */
    private function teamCost(array $team, History $history): float
    {
        $cost = 0.0;
        $count = count($team);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $together = $history->teammates($team[$i], $team[$j]);
                $cost += $this->config->teammateRepeat * $together * $together;

                if (isset($this->topSeeds[$team[$i]], $this->topSeeds[$team[$j]])) {
                    $cost += $this->config->topSeedsTogether;
                }
            }
        }

        return $cost;
    }
}
