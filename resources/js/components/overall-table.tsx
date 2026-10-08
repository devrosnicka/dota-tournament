import { Trophy } from 'lucide-react';
import { formatPoints } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { OverallState } from '@/types';

type Props = {
    overall: OverallState;
    highlightId?: number;
    size?: 'default' | 'tv';
};

export default function OverallTable({
    overall,
    highlightId,
    size = 'default',
}: Props) {
    const cell = size === 'tv' ? 'px-3 py-2' : 'px-2 py-1.5';

    return (
        <div className="overflow-x-auto">
            <table
                className={cn(
                    'w-full tabular-nums',
                    size === 'tv' ? 'text-3xl' : 'text-sm',
                )}
            >
                <thead className="text-left text-muted-foreground">
                    <tr className="border-b">
                        <th className={cn(cell, 'w-10 font-medium')}>#</th>
                        <th className={cn(cell, 'font-medium')}>Hráč</th>
                        <th className={cn(cell, 'text-right font-medium')}>
                            Zákl. část
                        </th>
                        <th className={cn(cell, 'text-right font-medium')}>
                            Finále
                        </th>
                        <th className={cn(cell, 'text-right font-medium')}>
                            Celkem
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {overall.rows.map((row) => (
                        <tr
                            key={row.playerId}
                            className={cn(
                                'border-b last:border-0',
                                row.playerId === highlightId &&
                                    'bg-primary/10 font-semibold',
                            )}
                        >
                            <td className={cell}>{row.position}.</td>
                            <td className={cn(cell, 'max-w-48 truncate')}>
                                {row.nick}
                                {row.champion && (
                                    <Trophy
                                        className="ml-1 inline size-[0.9em] text-amber-500"
                                        aria-label="Celkový šampion"
                                    />
                                )}
                            </td>
                            <td className={cn(cell, 'text-right')}>
                                {formatPoints(row.groupPoints)}
                            </td>
                            <td className={cn(cell, 'text-right')}>
                                {row.finalPoints}
                            </td>
                            <td
                                className={cn(cell, 'text-right font-semibold')}
                            >
                                {formatPoints(row.total)}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
