import { Trophy } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { ScheduleMatch, SchedulePlayer } from '@/types';

type Props = {
    match: ScheduleMatch;
    highlightId?: number;
    showSeeds?: boolean;
    seedDiff?: number;
    className?: string;
};

export default function MatchCard({
    match,
    highlightId,
    showSeeds = false,
    seedDiff,
    className,
}: Props) {
    const mine = [...match.teamA, ...match.teamB].some(
        (p) => p.id === highlightId,
    );

    return (
        <div
            className={cn(
                'rounded-lg border bg-card p-3',
                mine && 'border-primary ring-1 ring-primary',
                className,
            )}
        >
            <div className="mb-2 flex items-center justify-between text-xs text-muted-foreground">
                <span>{match.lobby ? `Lobby ${match.lobby}` : 'Zápas'}</span>
                {match.winner && (
                    <span className="font-medium text-foreground tabular-nums">
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
                />
                <Team
                    label="Tým B"
                    players={match.teamB}
                    won={match.winner === 'B'}
                    highlightId={highlightId}
                    showSeeds={showSeeds}
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
}: {
    label: string;
    players: SchedulePlayer[];
    won: boolean;
    highlightId?: number;
    showSeeds: boolean;
}) {
    const seeds = players.map((p) => p.seed ?? 0);
    const average = seeds.reduce((a, b) => a + b, 0) / (seeds.length || 1);

    return (
        <div>
            <div className="mb-1 flex items-center gap-1 text-xs font-medium text-muted-foreground uppercase">
                {label}
                {won && <Trophy className="size-3.5 text-amber-500" />}
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
                            'flex items-center gap-1 truncate rounded px-1 text-sm',
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
