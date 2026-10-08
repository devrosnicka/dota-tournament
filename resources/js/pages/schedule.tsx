import { Head } from '@inertiajs/react';
import MatchCard from '@/components/match-card';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { ScheduleRound } from '@/types';

type Props = {
    rounds: ScheduleRound[] | null;
    me: number;
};

export default function Schedule({ rounds, me }: Props) {
    return (
        <>
            <Head title="Rozpis" />
            <div className="grid grid-cols-1 gap-4">
                <h1 className="text-2xl font-bold">Rozpis</h1>
                {rounds === null ? (
                    <p className="text-muted-foreground">
                        Rozpis zatím není zveřejněný.
                    </p>
                ) : (
                    rounds.map((round) => (
                        <RoundCard key={round.id} round={round} me={me} />
                    ))
                )}
            </div>
        </>
    );
}

function RoundCard({ round, me }: { round: ScheduleRound; me: number }) {
    const sitting = round.sitters.some((p) => p.id === me);

    return (
        <Card className={cn(round.status === 'done' && 'opacity-80')}>
            <CardHeader>
                <CardTitle className="flex flex-wrap items-center gap-2">
                    {round.number}. kolo
                    <Badge variant="outline">{round.format}</Badge>
                    {round.status === 'done' && (
                        <Badge variant="secondary">odehráno</Badge>
                    )}
                    {sitting && <Badge>sedíš</Badge>}
                </CardTitle>
            </CardHeader>
            <CardContent className="grid gap-3">
                <div className="grid gap-3 sm:grid-cols-2">
                    {round.matches.map((match) => (
                        <MatchCard
                            key={match.id}
                            match={match}
                            highlightId={me}
                        />
                    ))}
                </div>
                {round.sitters.length > 0 && (
                    <p className="text-sm">
                        <span className="text-muted-foreground">Sedí: </span>
                        {round.sitters.map((p, index) => (
                            <span key={p.id}>
                                {index > 0 && ', '}
                                <span
                                    className={cn(
                                        p.id === me && 'font-semibold',
                                    )}
                                >
                                    {p.nick}
                                </span>
                            </span>
                        ))}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
