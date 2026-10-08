import { Head, usePoll } from '@inertiajs/react';
import StandingsTable from '@/components/standings-table';
import { Card, CardContent } from '@/components/ui/card';
import type { GroupStandings } from '@/types';

type Props = {
    standings: GroupStandings | null;
    me: number;
};

export default function Standings({ standings, me }: Props) {
    usePoll(10_000);

    return (
        <>
            <Head title="Tabulka" />
            <div className="grid grid-cols-1 gap-4">
                <h1 className="text-2xl font-bold">Tabulka základní části</h1>
                {standings ? (
                    <Card>
                        <CardContent>
                            <StandingsTable
                                standings={standings}
                                highlightId={me}
                            />
                            <p className="mt-3 text-xs text-muted-foreground">
                                Výhra 1 bod, sezení 0,5. Při shodě rozhoduje
                                rozdíl killů a pak nasazení. Do finále postupuje
                                10 nejlepších.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <p className="text-muted-foreground">
                        Tabulka bude k dispozici po zahájení základní části.
                    </p>
                )}
            </div>
        </>
    );
}
