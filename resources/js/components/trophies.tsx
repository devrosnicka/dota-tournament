import { Crown, Trophy } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { OverallState } from '@/types';

type Props = {
    overall: OverallState;
    size?: 'default' | 'tv';
};

export default function Trophies({ overall, size = 'default' }: Props) {
    const tv = size === 'tv';

    return (
        <div
            className={cn(
                'grid gap-4 sm:grid-cols-2',
                tv && 'grid-cols-2 gap-10',
            )}
        >
            <div
                className={cn(
                    'rounded-xl border bg-card p-4 text-center',
                    tv && 'p-10',
                )}
            >
                <Trophy
                    className={cn(
                        'mx-auto mb-2 text-amber-500',
                        tv ? 'size-24' : 'size-10',
                    )}
                />
                <div
                    className={cn(
                        'text-muted-foreground',
                        tv ? 'text-4xl' : 'text-sm',
                    )}
                >
                    Vítězný tým
                </div>
                <div
                    className={cn(
                        'mt-2 font-semibold',
                        tv ? 'text-5xl leading-tight' : 'text-lg',
                    )}
                >
                    {overall.winningTeam?.map((p) => p.nick).join(', ') ?? '–'}
                </div>
            </div>
            <div
                className={cn(
                    'rounded-xl border bg-card p-4 text-center',
                    tv && 'p-10',
                )}
            >
                <Crown
                    className={cn(
                        'mx-auto mb-2 text-amber-500',
                        tv ? 'size-24' : 'size-10',
                    )}
                />
                <div
                    className={cn(
                        'text-muted-foreground',
                        tv ? 'text-4xl' : 'text-sm',
                    )}
                >
                    Celkový šampion
                </div>
                <div
                    className={cn(
                        'mt-2 font-bold',
                        tv ? 'text-7xl' : 'text-2xl',
                    )}
                >
                    {overall.champion?.nick ?? 'rozhodne rozstřel'}
                </div>
            </div>
        </div>
    );
}
