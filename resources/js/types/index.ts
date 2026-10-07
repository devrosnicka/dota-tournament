export type * from './ui';

export type Phase =
    | 'registration'
    | 'ranking'
    | 'schedule_review'
    | 'group_stage'
    | 'final_draft'
    | 'final'
    | 'finished';
