import { Head, usePoll } from '@inertiajs/react';
import OverallTable from '@/components/overall-table';
import Trophies from '@/components/trophies';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { OverallState } from '@/types';

type Props = {
    overall: OverallState | null;
    final: boolean;
    me: number;
};

export default function Results({ overall, final, me }: Props) {
    usePoll(10_000, {}, { autoStart: !final });

    return (
        <>
            <Head title="Výsledky" />
            <div className="grid grid-cols-1 gap-4">
                <h1 className="text-2xl font-bold">Výsledky</h1>
                {!overall ? (
                    <p className="text-muted-foreground">
                        Celkové pořadí bude k dispozici během finále.
                    </p>
                ) : (
                    <>
                        {final && <Trophies overall={overall} />}
                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    {final
                                        ? 'Celkové pořadí'
                                        : 'Průběžné celkové pořadí'}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <OverallTable
                                    overall={overall}
                                    highlightId={me}
                                />
                                <p className="mt-3 text-xs text-muted-foreground">
                                    Celkem = body ze základní části + body z
                                    finále (mapa 1 bod, výhra v sérii 1 bod). O
                                    šampionovi při shodě rozhodují body z
                                    finále, pak rozstřel.
                                </p>
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </>
    );
}
