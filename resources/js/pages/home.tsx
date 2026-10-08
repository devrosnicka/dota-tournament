import { Head, Link, usePage, usePoll } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatPoints } from '@/lib/format';
import {
    final as finalPage,
    ranking as rankingPage,
    results,
    standings,
} from '@/routes';
import { show as showMatch } from '@/routes/matches';
import type { Phase } from '@/types';

const phaseInfo: Record<Phase, string> = {
    registration:
        'Jsi zaregistrovaný. Až se sejdou všichni, admin spustí vzájemné hodnocení hráčů.',
    ranking:
        'Seřaď ostatní hráče od nejlepšího po nejhoršího. Z hodnocení všech vznikne nasazení pro vyvážené týmy.',
    schedule_review: 'Admin připravuje rozpis základní části.',
    group_stage: 'Hraje se základní část.',
    final_draft: 'Kapitáni vybírají týmy do finále, pak si hráči volí role.',
    final: 'Hraje se finále, série na dvě vítězné mapy (Bo3).',
    finished: 'Turnaj skončil. Gratulujeme vítězům!',
};

type GroupStage = {
    round: number | null;
    sitting: boolean;
    match: {
        id: number;
        lobby: number | null;
        side: 'A' | 'B';
        teamA: string[];
        teamB: string[];
        winner: 'A' | 'B' | null;
        killsA: number | null;
        killsB: number | null;
    } | null;
    standing: { position: number | null; points: number } | null;
};

type Props = {
    ranking: { submitted: boolean } | null;
    groupStage: GroupStage | null;
};

export default function Home({ ranking, groupStage }: Props) {
    const { phase, auth } = usePage().props;

    usePoll(10_000, {}, { autoStart: groupStage !== null });

    return (
        <>
            <Head title="Domů" />
            <div className="grid grid-cols-1 gap-4">
                <h1 className="text-2xl font-bold">
                    Ahoj, {auth.player?.nick}
                </h1>
                <Card>
                    <CardHeader>
                        <CardTitle>{phase.label}</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <p className="text-muted-foreground">
                            {phaseInfo[phase.value]}
                        </p>
                        {(phase.value === 'final_draft' ||
                            phase.value === 'final') && (
                            <Button asChild className="justify-self-start">
                                <Link href={finalPage()}>Finále</Link>
                            </Button>
                        )}
                        {phase.value === 'finished' && (
                            <Button asChild className="justify-self-start">
                                <Link href={results()}>Výsledky a trofeje</Link>
                            </Button>
                        )}
                        {ranking && (
                            <div className="flex items-center gap-3">
                                <Button asChild>
                                    <Link href={rankingPage()}>
                                        {ranking.submitted
                                            ? 'Upravit pořadí'
                                            : 'Seřadit hráče'}
                                    </Link>
                                </Button>
                                <Badge
                                    variant={
                                        ranking.submitted
                                            ? 'default'
                                            : 'outline'
                                    }
                                >
                                    {ranking.submitted
                                        ? 'Odesláno'
                                        : 'Neodesláno'}
                                </Badge>
                            </div>
                        )}
                    </CardContent>
                </Card>
                {groupStage && <GroupStageCard groupStage={groupStage} />}
            </div>
        </>
    );
}

function GroupStageCard({ groupStage }: { groupStage: GroupStage }) {
    const { round, sitting, match, standing } = groupStage;

    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    {round ? `${round}. kolo` : 'Všechna kola jsou odehraná'}
                </CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4">
                {sitting && (
                    <p>
                        V tomhle kole <strong>sedíš</strong> a dostaneš 0,5
                        bodu.
                    </p>
                )}
                {match && (
                    <div className="grid gap-3">
                        <p>
                            Hraješ{' '}
                            {match.lobby ? `v lobby ${match.lobby} ` : ''}za{' '}
                            <strong>tým {match.side}</strong>.
                        </p>
                        <div className="grid grid-cols-2 gap-3 text-sm">
                            {(['A', 'B'] as const).map((side) => (
                                <div key={side}>
                                    <div className="mb-1 text-xs font-medium text-muted-foreground uppercase">
                                        Tým {side}
                                        {match.winner === side && (
                                            <Trophy className="ml-1 inline size-3.5 text-amber-500" />
                                        )}
                                    </div>
                                    {(side === 'A'
                                        ? match.teamA
                                        : match.teamB
                                    ).join(', ')}
                                </div>
                            ))}
                        </div>
                        {match.winner && (
                            <p className="text-sm text-muted-foreground">
                                Výsledek: vyhrál tým {match.winner}, killy{' '}
                                {match.killsA} : {match.killsB}.
                            </p>
                        )}
                        <Button
                            asChild
                            variant={match.winner ? 'outline' : 'default'}
                        >
                            <Link href={showMatch(match.id)}>
                                {match.winner
                                    ? 'Upravit výsledek'
                                    : 'Zadat výsledek'}
                            </Link>
                        </Button>
                    </div>
                )}
                {standing && (
                    <div className="flex items-center justify-between border-t pt-3 text-sm">
                        <span>
                            {standing.position
                                ? `${standing.position}. místo`
                                : 'Mimo pořadí'}{' '}
                            · {formatPoints(standing.points)} b.
                        </span>
                        <Link
                            href={standings()}
                            className="underline underline-offset-4"
                        >
                            Tabulka
                        </Link>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
