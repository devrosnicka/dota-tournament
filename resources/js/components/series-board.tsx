import { teamName } from '@/lib/final';
import { cn } from '@/lib/utils';
import type { FinalState } from '@/types';

type Props = {
    final: FinalState;
    size?: 'default' | 'tv';
};

/**
 * Bo3 score, map results and who decides before the next map.
 */
export default function SeriesBoard({ final, size = 'default' }: Props) {
    const { series } = final;
    const tv = size === 'tv';
    const name = (side: 'A' | 'B') => teamName(final, side);

    return (
        <div className={cn('grid gap-3', tv && 'gap-6')}>
            <div
                className={cn(
                    'flex items-center justify-center gap-4 font-display font-bold tabular-nums',
                    tv ? 'text-7xl' : 'text-3xl',
                )}
            >
                <span className="min-w-0 flex-1 truncate text-right text-[0.5em] font-semibold">
                    {name('A')}
                </span>
                <span className="shrink-0">
                    {series.wins.A} : {series.wins.B}
                </span>
                <span className="min-w-0 flex-1 truncate text-[0.5em] font-semibold">
                    {name('B')}
                </span>
            </div>
            <ul className={cn('grid gap-1', tv ? 'text-3xl' : 'text-sm')}>
                {series.maps.map((map) => (
                    <li key={map.number}>
                        <strong>{map.number}. mapa:</strong> vyhrál{' '}
                        {name(map.winner)}
                        {map.killsA !== null && map.killsB !== null && (
                            <>
                                {' '}
                                ({map.killsA} : {map.killsB})
                            </>
                        )}
                        {map.radiant && <>, Radiant {name(map.radiant)}</>}
                        {map.firstPick && (
                            <>, první pick {name(map.firstPick)}</>
                        )}
                    </li>
                ))}
            </ul>
            {series.nextMap && series.chooser && (
                <p
                    className={cn(
                        'text-muted-foreground',
                        tv ? 'text-3xl' : 'text-sm',
                    )}
                >
                    {series.nextMap}. mapa:{' '}
                    <strong className="text-foreground">
                        {name(series.chooser)}
                    </strong>{' '}
                    {series.nextMap === 1
                        ? 'volí stranu i první pick.'
                        : 'volí stranu, nebo první pick. Druhý tým dostane to druhé.'}
                </p>
            )}
            {series.winner && (
                <p
                    className={cn(
                        'text-center font-semibold',
                        tv ? 'text-5xl' : 'text-lg',
                    )}
                >
                    Vítězný tým: {name(series.winner)}
                </p>
            )}
        </div>
    );
}
