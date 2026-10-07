<?php

namespace App\Enums;

/**
 * Tournament phases in the order they happen (SPEC §2.3).
 */
enum Phase: string
{
    case Registration = 'registration';
    case Ranking = 'ranking';
    case ScheduleReview = 'schedule_review';
    case GroupStage = 'group_stage';
    case FinalDraft = 'final_draft';
    case Final = 'final';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Registrace',
            self::Ranking => 'Hodnocení hráčů',
            self::ScheduleReview => 'Příprava rozpisu',
            self::GroupStage => 'Základní část',
            self::FinalDraft => 'Draft finále',
            self::Final => 'Finále',
            self::Finished => 'Vyhlášení',
        };
    }

    public function next(): ?self
    {
        $cases = self::cases();
        $index = array_search($this, $cases, true);

        return $cases[$index + 1] ?? null;
    }
}
