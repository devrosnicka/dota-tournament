export type SchedulePlayer = { id: number; nick: string; seed?: number | null };

export type ScheduleMatch = {
    id: number;
    lobby: number | null;
    teamA: SchedulePlayer[];
    teamB: SchedulePlayer[];
    winner: 'A' | 'B' | null;
    killsA: number | null;
    killsB: number | null;
};

export type ScheduleRound = {
    id: number;
    number: number;
    format: string;
    status: 'planned' | 'in_progress' | 'done';
    sitters: SchedulePlayer[];
    matches: ScheduleMatch[];
};
