import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import MatchCard from '@/components/match-card';
import ResultForm from '@/components/result-form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import { home } from '@/routes';
import { report } from '@/routes/matches';
import type { SchedulePlayer } from '@/types';

type Props = {
    match: {
        id: number;
        round: number | null;
        lobby: number | null;
        teamA: SchedulePlayer[];
        teamB: SchedulePlayer[];
        winner: 'A' | 'B' | null;
        killsA: number | null;
        killsB: number | null;
        reportedAt: string | null;
        reportedBy: string | null;
    };
    me: number;
    canReport: boolean;
};

export default function Match({ match, me, canReport }: Props) {
    return (
        <>
            <Head title={`${match.round}. kolo`} />
            <div className="grid grid-cols-1 gap-4">
                <Link
                    href={home()}
                    className="flex items-center gap-1 text-sm text-muted-foreground"
                >
                    <ArrowLeft className="size-4" /> Domů
                </Link>
                <h1 className="text-2xl font-bold">
                    {match.round}. kolo
                    {match.lobby ? ` · Lobby ${match.lobby}` : ''}
                </h1>
                <MatchCard match={match} highlightId={me} />
                {match.reportedAt && (
                    <p className="text-sm text-muted-foreground">
                        Výsledek zadal {match.reportedBy ?? 'admin'},{' '}
                        {formatDateTime(match.reportedAt)}.
                    </p>
                )}
                {canReport && (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {match.winner
                                    ? 'Oprava výsledku'
                                    : 'Výsledek zápasu'}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResultForm
                                form={report.form(match.id)}
                                winner={match.winner}
                                killsA={match.killsA}
                                killsB={match.killsB}
                            />
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
