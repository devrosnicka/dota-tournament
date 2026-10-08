export type OverallPlayer = { id: number; nick: string };

export type OverallRow = {
    playerId: number;
    nick: string;
    position: number;
    groupPoints: number;
    finalPoints: number;
    total: number;
    champion: boolean;
};

export type OverallState = {
    rows: OverallRow[];
    champion: OverallPlayer | null;
    winningTeam: OverallPlayer[] | null;
    championTie: { players: OverallPlayer[]; resolved: boolean } | null;
};
