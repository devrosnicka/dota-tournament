<?php

namespace App\Tournament;

use App\Domain\Support\Lottery;
use App\Enums\Phase;
use App\Models\Setting;

/**
 * Key/value tournament state stored in the `settings` table (phase, number
 * of rounds, RNG seeds). Values are loaded once per request.
 */
final class TournamentSettings
{
    /** @var array<string, mixed>|null */
    private ?array $values = null;

    public function phase(): Phase
    {
        $value = $this->get('phase');

        return is_string($value) ? Phase::from($value) : Phase::Registration;
    }

    public function setPhase(Phase $phase): void
    {
        $this->set('phase', $phase->value);
    }

    /**
     * Deterministic lottery from a random seed created on first use.
     */
    public function lottery(): Lottery
    {
        $seed = $this->get('lottery_seed');

        if (! is_int($seed)) {
            $seed = random_int(1, PHP_INT_MAX);
            $this->set('lottery_seed', $seed);
        }

        return new Lottery($seed);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->values ??= Setting::query()->pluck('value', 'key')->all();

        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }

    public function set(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        if ($this->values !== null) {
            $this->values[$key] = $value;
        }
    }
}
