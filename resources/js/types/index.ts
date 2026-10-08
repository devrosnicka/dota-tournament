export type * from './ui';

export type Phase =
    | 'registration'
    | 'ranking'
    | 'schedule_review'
    | 'group_stage'
    | 'final_draft'
    | 'final'
    | 'finished';

export type PhaseInfo = { value: Phase; label: string };

export type Auth = {
    player: { id: number; nick: string } | null;
    isAdmin: boolean;
};
