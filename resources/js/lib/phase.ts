import type { Phase } from '@/types';

const order: Phase[] = [
    'registration',
    'ranking',
    'schedule_review',
    'group_stage',
    'final_draft',
    'final',
    'finished',
];

export function isAtLeast(phase: Phase, other: Phase): boolean {
    return order.indexOf(phase) >= order.indexOf(other);
}
