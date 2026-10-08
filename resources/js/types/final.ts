export type FinalSide = 'A' | 'B';

export type FinalPlayer = {
    id: number;
    nick: string;
    placement: number;
    role: number | null;
};

export type FinalMap = {
    number: number;
    winner: FinalSide;
    killsA: number | null;
    killsB: number | null;
    radiant: FinalSide | null;
    firstPick: FinalSide | null;
};

export type FinalState = {
    stage: 'advantage' | 'picks' | 'roles' | 'done';
    advantage: 'player_pick' | 'side_pick' | null;
    captains: Record<FinalSide, FinalPlayer>;
    firstPickSide: FinalSide | null;
    pickOrder: FinalSide[];
    picksMade: number;
    pickingSide: FinalSide | null;
    pool: FinalPlayer[];
    teams: Record<FinalSide, FinalPlayer[]>;
    roleTurn: Record<FinalSide, number | null>;
    freeRoles: Record<FinalSide, number[]>;
    series: {
        maps: FinalMap[];
        wins: Record<FinalSide, number>;
        winner: FinalSide | null;
        nextMap: number | null;
        chooser: FinalSide | null;
        sidePickHolder: FinalSide | null;
    };
    you: {
        side: FinalSide | null;
        canChooseAdvantage: boolean;
        canPick: boolean;
        canChooseRole: boolean;
    };
};
