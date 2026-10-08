import { Swords } from 'lucide-react';
import { Fragment } from 'react';
import { formatDiff, formatPoints } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { GroupStandings } from '@/types';

type Props = {
    standings: GroupStandings;
    highlightId?: number;
    size?: 'default' | 'tv';
};

/**
 * Group stage table with the qualification line after the 10th active player.
 */
export default function StandingsTable({
    standings,
    highlightId,
    size = 'default',
}: Props) {
    const { rows } = standings;
    const activeCount = rows.filter((r) => r.active).length;
    const cell = size === 'tv' ? 'px-3 py-2' : 'px-2 py-1.5';

    return (
        <div className="overflow-x-auto">
            <table
                className={cn(
                    'w-full tabular-nums',
                    size === 'tv' ? 'text-2xl' : 'text-sm',
                )}
            >
                <thead className="text-left text-muted-foreground">
                    <tr className="border-b">
                        <th className={cn(cell, 'w-10 font-medium')}>#</th>
                        <th className={cn(cell, 'font-medium')}>Hráč</th>
                        <th className={cn(cell, 'text-right font-medium')}>
                            Body
                        </th>
                        <th className={cn(cell, 'text-right font-medium')}>
                            V–P
                        </th>
                        <th className={cn(cell, 'text-right font-medium')}>
                            Sezení
                        </th>
                        <th className={cn(cell, 'text-right font-medium')}>
                            Killy
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row, index) => (
                        <Fragment key={row.playerId}>
                            {index === 10 && row.active && activeCount > 10 && (
                                <tr aria-hidden>
                                    <td colSpan={6} className="p-0">
                                        <div className="border-t-2 border-dashed border-primary/60" />
                                    </td>
                                </tr>
                            )}
                            <tr
                                className={cn(
                                    'border-b last:border-0',
                                    !row.active &&
                                        'text-muted-foreground line-through decoration-1',
                                    row.playerId === highlightId &&
                                        'bg-primary/10 font-semibold',
                                )}
                            >
                                <td className={cell}>
                                    {row.position ? `${row.position}.` : '–'}
                                </td>
                                <td className={cn(cell, 'max-w-40 truncate')}>
                                    {row.nick}
                                    {row.tied && (
                                        <Swords
                                            className="ml-1 inline size-[0.9em] text-amber-600"
                                            aria-label="Shoda na hranici postupu"
                                        />
                                    )}
                                </td>
                                <td
                                    className={cn(
                                        cell,
                                        'text-right font-semibold',
                                    )}
                                >
                                    {formatPoints(row.points)}
                                </td>
                                <td className={cn(cell, 'text-right')}>
                                    {row.wins}–{row.losses}
                                </td>
                                <td className={cn(cell, 'text-right')}>
                                    {row.sits}
                                </td>
                                <td className={cn(cell, 'text-right')}>
                                    {formatDiff(row.killDiff)}
                                </td>
                            </tr>
                        </Fragment>
                    ))}
                </tbody>
            </table>
            {standings.tie && (
                <p
                    className={cn(
                        'mt-2 text-muted-foreground',
                        size === 'tv' ? 'text-xl' : 'text-xs',
                    )}
                >
                    <Swords className="mr-1 inline size-[1em]" />
                    {standings.tie.resolved
                        ? 'Rozhodnuto rozstřelem'
                        : 'Shoda na hranici postupu'}
                    : {standings.tie.players.join(', ')} (postupuje{' '}
                    {standings.tie.spots}).
                </p>
            )}
        </div>
    );
}
