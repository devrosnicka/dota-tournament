import { Trophy } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { ScheduleMatch, SchedulePlayer } from '@/types';

type Props = {
    match: ScheduleMatch;
    highlightId?: number;
    showSeeds?: boolean;
    seedDiff?: number;
    size?: 'default' | 'tv';
    className?: string;
};

export default function MatchCard({
    match,
    highlightId,
    showSeeds = false,
    seedDiff,
    size = 'default',
    className,
}: Props) {
    const mine = [...match.teamA, ...match.teamB].some(
        (p) => p.id === highlightId,
    );
    const tv = size === 'tv';

    return (
        <div
            className={cn(
                'rounded-lg border bg-card',
                tv ? 'p-6' : 'p-3',
                mine && 'border-primary ring-1 ring-primary',
                className,
            )}
        >
            <div
                className={cn(
                    'mb-2 flex items-center justify-between text-muted-foreground',
                    tv ? 'text-2xl' : 'text-xs',
                )}
            >
                <span>{match.lobby ? `Lobby ${match.lobby}` : 'Zápas'}</span>
                {match.winner && (
                    <span
                        className={cn(
                            'font-medium text-foreground tabular-nums',
                            tv && 'text-4xl font-bold',
                        )}
                    >
                        {match.killsA} : {match.killsB}
                    </span>
                )}
                {seedDiff !== undefined && (
                    <span>
                        rozdíl ⌀ nasazení{' '}
                        {seedDiff.toLocaleString('cs-CZ', {
                            maximumFractionDigits: 2,
                        })}
                    </span>
                )}
            </div>
            <div className="grid grid-cols-2 gap-3">
                <Team
                    label="Tým A"
                    players={match.teamA}
                    won={match.winner === 'A'}
                    highlightId={highlightId}
                    showSeeds={showSeeds}
                    tv={tv}
                />
                <Team
                    label="Tým B"
                    players={match.teamB}
                    won={match.winner === 'B'}
                    highlightId={highlightId}
                    showSeeds={showSeeds}
                    tv={tv}
                />
            </div>
        </div>
    );
}

function Team({
    label,
    players,
    won,
    highlightId,
    showSeeds,
    tv,
}: {
    label: string;
    players: SchedulePlayer[];
    won: boolean;
    highlightId?: number;
    showSeeds: boolean;
    tv: boolean;
}) {
    const seeds = players.map((p) => p.seed ?? 0);
    const average = seeds.reduce((a, b) => a + b, 0) / (seeds.length || 1);

    return (
        <div>
            <div
                className={cn(
                    'mb-1 flex items-center gap-1 font-medium text-muted-foreground uppercase',
                    tv ? 'text-xl' : 'text-xs',
                )}
            >
                {label}
                {won && (
                    <Trophy
                        className={cn(
                            'text-amber-500',
                            tv ? 'size-6' : 'size-3.5',
                        )}
                    />
                )}
                {showSeeds && (
                    <span className="ml-auto font-normal normal-case">
                        ⌀{' '}
                        {average.toLocaleString('cs-CZ', {
                            maximumFractionDigits: 1,
                        })}
                    </span>
                )}
            </div>
            <ul className="grid gap-0.5">
                {players.map((player) => (
                    <li
                        key={player.id}
                        className={cn(
                            'flex items-center gap-1 truncate rounded px-1',
                            tv ? 'text-4xl leading-snug' : 'text-sm',
                            player.id === highlightId &&
                                'bg-primary font-semibold text-primary-foreground',
                        )}
                    >
                        <span className="truncate">{player.nick}</span>
                        {showSeeds && (
                            <span className="ml-auto text-xs text-muted-foreground tabular-nums">
                                {player.seed}
                            </span>
                        )}
                    </li>
                ))}
            </ul>
        </div>
    );
}
