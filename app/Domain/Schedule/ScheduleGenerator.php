<?php

namespace App\Domain\Schedule;

use App\Domain\Support\Rng;

/**
 * Generates rounds of the group stage: who sits, who plays in which match
 * and on which side (SPEC §1.6).
 *
 * Rounds are generated one after another so that the history of teammates
 * and opponents carries over. Each round takes the cheapest of K random
 * splits (all 126 splits for a single 5v5). The whole generation runs M
 * times with seeds derived from the given one and the cheapest result wins,
 * so the same seed always gives the same schedule.
 */
final class ScheduleGenerator
{
    public function __construct(
        private readonly GeneratorConfig $config,
        private readonly FormatResolver $formats = new FormatResolver,
        private readonly SitPlanner $sitPlanner = new SitPlanner,
    ) {}

    /**
     * @param  list<int>  $players  active players from the strongest seed
     * @param  History  $history  earlier rounds that stay as they are
     */
    public function generate(array $players, int $rounds, History $history, int $seed): GeneratedSchedule
    {
        $format = $this->formats->resolve(count($players));
        $positions = [];

        foreach ($players as $index => $player) {
            $positions[$player] = $index + 1;
        }

        $cost = new TeamCost($this->config, $positions);
        $best = null;

        for ($restart = 0; $restart < max(1, $this->config->restarts); $restart++) {
            $rng = new Rng(Rng::derive($seed, "restart:{$restart}"));
            [$plans, $total] = $this->attempt($players, $format, $rounds, clone $history, $cost, $rng);

            if ($best === null || $total < $best[1]) {
                $best = [$plans, $total];
            }
        }

        return new GeneratedSchedule($best[0], $best[1], $seed);
    }

    /**
     * @param  list<int>  $players
     * @return array{list<RoundPlan>, float}
     */
    private function attempt(array $players, RoundFormat $format, int $rounds, History $history, TeamCost $cost, Rng $rng): array
    {
        $sitPlan = $this->sitPlanner->plan(
            $players,
            array_fill(0, $rounds, $format->sitting()),
            $history->sits,
            $history->lastSitters,
            $rng,
        );

        $plans = [];
        $total = 0.0;

        foreach ($sitPlan as $sitters) {
            $playing = array_values(array_diff($players, $sitters));
            [$matches, $roundCost] = $this->bestSplit($playing, $format, $history, $cost, $rng);

            $round = new RoundPlan($format, $sitters, $matches);
            $history->recordRound($round);
            $plans[] = $round;
            $total += $roundCost;
        }

        return [$plans, $total];
    }

    /**
     * @param  list<int>  $playing
     * @return array{list<MatchPlan>, float}
     */
    private function bestSplit(array $playing, RoundFormat $format, History $history, TeamCost $cost, Rng $rng): array
    {
        $best = null;
        $bestCost = INF;

        foreach ($this->splits($rng->shuffle($playing), $format, $rng) as $matches) {
            $candidate = $cost->cost($matches, $history);

            if ($candidate < $bestCost) {
                [$best, $bestCost] = [$matches, $candidate];

                if ($candidate === 0.0) {
                    break;
                }
            }
        }

        return [$best ?? [], $bestCost];
    }

    /**
     * @param  list<int>  $playing  in random order
     * @return iterable<list<MatchPlan>>
     */
    private function splits(array $playing, RoundFormat $format, Rng $rng): iterable
    {
        if ($format->matches === 1 && count($playing) === 10) {
            // All 126 ways to split ten players into two teams of five.
            $first = $playing[0];
            $rest = array_slice($playing, 1);

            foreach (self::combinations($rest, 4) as $mates) {
                yield [new MatchPlan([$first, ...$mates], array_values(array_diff($rest, $mates)))];
            }

            return;
        }

        $size = $format->teamSize;

        for ($i = 0; $i < $this->config->samplesPerRound; $i++) {
            $order = $i === 0 ? $playing : $rng->shuffle($playing);
            $matches = [];

            foreach (array_chunk($order, max(1, $size * 2)) as $group) {
                $matches[] = new MatchPlan(array_slice($group, 0, $size), array_slice($group, $size));
            }

            yield $matches;
        }
    }

    /**
     * @param  list<int>  $items
     * @return iterable<list<int>>
     */
    private static function combinations(array $items, int $size, int $start = 0): iterable
    {
        if ($size === 0) {
            yield [];

            return;
        }

        for ($i = $start; $i <= count($items) - $size; $i++) {
            foreach (self::combinations($items, $size - 1, $i + 1) as $tail) {
                yield [$items[$i], ...$tail];
            }
        }
    }
}
