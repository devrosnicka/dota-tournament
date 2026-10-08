export type StandingRow = {
    playerId: number;
    nick: string;
    position: number | null;
    points: number;
    wins: number;
    losses: number;
    sits: number;
    killDiff: number;
    active: boolean;
    qualified: boolean;
    tied: boolean;
};

export type GroupStandings = {
    rows: StandingRow[];
    tie: { players: string[]; spots: number; resolved: boolean } | null;
};
