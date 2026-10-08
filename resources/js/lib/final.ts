import type { FinalSide, FinalState } from '@/types';

export const roleLabels: Record<number, string> = {
    1: 'Carry',
    2: 'Mid',
    3: 'Offlane',
    4: 'Soft support',
    5: 'Hard support',
};

export function teamName(final: FinalState, side: FinalSide): string {
    return `Tým ${final.captains[side].nick}`;
}
