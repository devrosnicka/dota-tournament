import { Head, router, usePoll } from '@inertiajs/react';
import { useState } from 'react';
import MatchCard from '@/components/match-card';
import ResultForm from '@/components/result-form';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { report } from '@/routes/admin/matches';
import { close, reopen } from '@/routes/admin/rounds';
import type { ScheduleMatch, ScheduleRound } from '@/types';

type Props = {
    rounds: ScheduleRound[];
    currentRound: number | null;
    editable: boolean;
};

const statusLabel = {
    planned: 'nezačalo',
    in_progress: 'hraje se',
    done: 'uzavřené',
} as const;

export default function Results({ rounds, currentRound, editable }: Props) {
    usePoll(10_000);

    const lastDone = Math.max(
        0,
        ...rounds.filter((r) => r.status === 'done').map((r) => r.number),
    );

    return (
        <>
            <Head title="Výsledky" />
            <div className="grid grid-cols-1 gap-6">
                {rounds.length === 0 && (
                    <p className="text-muted-foreground">
                        Rozpis ještě neexistuje.
                    </p>
                )}
                {rounds.map((round) => {
                    const complete = round.matches.every(
                        (m) => m.winner !== null,
                    );

                    return (
                        <Card
                            key={round.id}
                            className={
                                round.number === currentRound
                                    ? 'ring-2 ring-primary'
                                    : ''
                            }
                        >
                            <CardHeader>
                                <CardTitle className="flex flex-wrap items-center gap-2">
                                    {round.number}. kolo
                                    <Badge variant="outline">
                                        {round.format}
                                    </Badge>
                                    <Badge
                                        variant={
                                            round.status === 'done'
                                                ? 'secondary'
                                                : 'outline'
                                        }
                                    >
                                        {statusLabel[round.status]}
                                    </Badge>
                                    {editable && round.status !== 'done' && (
                                        <Button
                                            size="sm"
                                            className="ml-auto"
                                            disabled={!complete}
                                            onClick={() =>
                                                router.visit(close(round.id), {
                                                    preserveScroll: true,
                                                })
                                            }
                                        >
                                            Uzavřít kolo
                                        </Button>
                                    )}
                                    {editable &&
                                        round.status === 'done' &&
                                        round.number === lastDone && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="ml-auto"
                                                onClick={() =>
                                                    router.visit(
                                                        reopen(round.id),
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                Znovu otevřít
                                            </Button>
                                        )}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3 md:grid-cols-2">
                                {round.matches.map((match) => (
                                    <AdminMatch
                                        key={match.id}
                                        match={match}
                                        editable={editable}
                                    />
                                ))}
                                {round.sitters.length > 0 && (
                                    <p className="text-sm md:col-span-2">
                                        <span className="text-muted-foreground">
                                            Sedí:{' '}
                                        </span>
                                        {round.sitters
                                            .map((p) => p.nick)
                                            .join(', ')}
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    );
                })}
            </div>
        </>
    );
}

function AdminMatch({
    match,
    editable,
}: {
    match: ScheduleMatch;
    editable: boolean;
}) {
    const [editing, setEditing] = useState(false);

    return (
        <div className="grid gap-2">
            <MatchCard match={match} />
            {editable &&
                (editing ? (
                    <ResultForm
                        form={report.form(match.id)}
                        winner={match.winner}
                        killsA={match.killsA}
                        killsB={match.killsB}
                        compact
                        onSuccess={() => setEditing(false)}
                    />
                ) : (
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => setEditing(true)}
                    >
                        {match.winner ? 'Opravit výsledek' : 'Zadat výsledek'}
                    </Button>
                ))}
        </div>
    );
}
