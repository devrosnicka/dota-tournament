import { Crown } from 'lucide-react';
import { roleLabels, teamName } from '@/lib/final';
import { cn } from '@/lib/utils';
import type { FinalSide, FinalState } from '@/types';

type Props = {
    final: FinalState;
    highlightId?: number;
    size?: 'default' | 'tv';
};

/**
 * Both teams of the final with roles, and the players still to be picked.
 */
export default function FinalBoard({
    final,
    highlightId,
    size = 'default',
}: Props) {
    const tv = size === 'tv';

    return (
        <div className={cn('grid gap-4', tv && 'gap-8')}>
            <div
                className={cn(
                    'grid gap-3',
                    tv ? 'grid-cols-2 gap-8' : 'sm:grid-cols-2',
                )}
            >
                {(['A', 'B'] as const).map((side) => (
                    <Team
                        key={side}
                        final={final}
                        side={side}
                        highlightId={highlightId}
                        tv={tv}
                    />
                ))}
            </div>
            {final.pool.length > 0 && (
                <div>
                    <h3
                        className={cn(
                            'mb-2 font-medium text-muted-foreground',
                            tv ? 'text-3xl' : 'text-sm',
                        )}
                    >
                        Zbývá vybrat
                    </h3>
                    <ul className={cn('flex flex-wrap gap-2', tv && 'gap-4')}>
                        {final.pool.map((player) => (
                            <li
                                key={player.id}
                                className={cn(
                                    'rounded-md border bg-card px-2 py-1',
                                    tv ? 'px-5 py-2 text-4xl' : 'text-sm',
                                )}
                            >
                                <span className="text-muted-foreground">
                                    {player.placement}.
                                </span>{' '}
                                {player.nick}
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}

function Team({
    final,
    side,
    highlightId,
    tv,
}: {
    final: FinalState;
    side: FinalSide;
    highlightId?: number;
    tv: boolean;
}) {
    const picking = final.pickingSide === side;
    const roleTurn = final.stage === 'roles' ? final.roleTurn[side] : null;

    return (
        <div
            className={cn(
                'min-w-0 rounded-lg border bg-card p-3',
                tv && 'p-6',
                picking && 'border-primary ring-2 ring-primary',
            )}
        >
            <h3
                className={cn(
                    'mb-2 flex items-center justify-between gap-2 font-semibold',
                    tv ? 'text-4xl' : 'text-sm',
                )}
            >
                <span className="truncate">{teamName(final, side)}</span>
                {final.series.winner === side && (
                    <span className="text-amber-500">vítěz</span>
                )}
            </h3>
            <ul className={cn('grid gap-1', tv && 'gap-3')}>
                {final.teams[side].map((player) => (
                    <li
                        key={player.id}
                        className={cn(
                            'flex min-w-0 items-center gap-2 rounded px-1',
                            tv ? 'text-4xl' : 'text-sm',
                            player.id === highlightId &&
                                'bg-primary/10 font-semibold',
                            player.id === roleTurn && 'ring-2 ring-primary',
                        )}
                    >
                        <span className="min-w-0 truncate">{player.nick}</span>
                        {player.id === final.captains[side].id && (
                            <Crown
                                className={cn(
                                    'shrink-0 text-amber-500',
                                    tv ? 'size-8' : 'size-3.5',
                                )}
                                aria-label="kapitán"
                            />
                        )}
                        <span
                            className={cn(
                                'ml-auto shrink-0 text-muted-foreground',
                                tv ? 'text-2xl' : 'text-xs',
                            )}
                        >
                            {player.role
                                ? `${player.role} · ${roleLabels[player.role]}`
                                : `${player.placement}. místo`}
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
