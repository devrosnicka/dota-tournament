import { Form, Head, router } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { useState } from 'react';
import ConfirmDialog from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import MatchCard from '@/components/match-card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { advance } from '@/routes/admin/phase';
import { generate, swap } from '@/routes/admin/schedule';
import type { ScheduleRound, SchedulePlayer } from '@/types';

type Analysis = {
    maxTeammates: number;
    sits: { nick: string; count: number }[];
    seedDiffs: number[][];
    warnings: string[];
};

type Props = {
    rounds: ScheduleRound[];
    analysis: Analysis | null;
    roundsSetting: number;
    maxRounds: number;
    editable: boolean;
    seed: number | null;
};

const selectClass =
    'h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs';

export default function Schedule({
    rounds,
    analysis,
    roundsSetting,
    maxRounds,
    editable,
    seed,
}: Props) {
    const exists = rounds.length > 0;

    return (
        <>
            <Head title="Rozpis" />
            <div className="grid grid-cols-1 gap-6">
                {editable && (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {exists
                                    ? 'Náhled rozpisu'
                                    : 'Generování rozpisu'}
                            </CardTitle>
                            <CardDescription>
                                Rozpis vychází z uzavřeného nasazení. Dokud není
                                zveřejněný, vidí ho jen admin.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <Form
                                {...generate.form()}
                                options={{ preserveScroll: true }}
                                className="flex flex-wrap items-end gap-3"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="rounds">
                                                Počet kol
                                            </Label>
                                            <Input
                                                id="rounds"
                                                name="rounds"
                                                type="number"
                                                min={1}
                                                max={maxRounds}
                                                defaultValue={roundsSetting}
                                                className="w-24"
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            variant={
                                                exists ? 'outline' : 'default'
                                            }
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            {exists
                                                ? 'Přegenerovat'
                                                : 'Vygenerovat rozpis'}
                                        </Button>
                                        {exists && (
                                            <ConfirmDialog
                                                trigger={
                                                    <Button>
                                                        Zveřejnit a zahájit
                                                    </Button>
                                                }
                                                title="Zveřejnit rozpis a zahájit základní část?"
                                                description="Rozpis uvidí všichni hráči. Pak už ho nepůjde přegenerovat ani upravit."
                                                confirmLabel="Zveřejnit"
                                                onConfirm={() =>
                                                    router.visit(advance())
                                                }
                                            />
                                        )}
                                        <InputError
                                            message={errors.rounds}
                                            className="w-full"
                                        />
                                    </>
                                )}
                            </Form>
                            {seed !== null && exists && (
                                <p className="text-xs text-muted-foreground">
                                    Seed generátoru: {seed}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}

                {!exists && !editable && (
                    <p className="text-muted-foreground">
                        Rozpis se generuje ve fázi přípravy rozpisu.
                    </p>
                )}

                {analysis && <AnalysisCard analysis={analysis} />}

                {rounds.map((round, index) => (
                    <RoundCard
                        key={round.id}
                        round={round}
                        seedDiffs={analysis?.seedDiffs[index] ?? []}
                        editable={editable}
                    />
                ))}
            </div>
        </>
    );
}

function AnalysisCard({ analysis }: { analysis: Analysis }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Statistiky</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4">
                {analysis.warnings.length > 0 && (
                    <Alert variant="destructive">
                        <AlertTriangle />
                        <AlertTitle>Porušená pravidla sezení</AlertTitle>
                        <AlertDescription>
                            <ul className="list-disc pl-4">
                                {analysis.warnings.map((warning) => (
                                    <li key={warning}>{warning}</li>
                                ))}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}
                <p className="text-sm">
                    Nejvíc společných zápasů jedné dvojice spoluhráčů:{' '}
                    <strong>{analysis.maxTeammates}</strong>
                </p>
                <div className="grid gap-2">
                    <p className="text-sm">Počet sezení:</p>
                    <div className="flex flex-wrap gap-1">
                        {analysis.sits.map((sit) => (
                            <Badge
                                key={sit.nick}
                                variant={
                                    sit.count > 0 ? 'secondary' : 'outline'
                                }
                            >
                                {sit.nick} × {sit.count}
                            </Badge>
                        ))}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function RoundCard({
    round,
    seedDiffs,
    editable,
}: {
    round: ScheduleRound;
    seedDiffs: number[];
    editable: boolean;
}) {
    const players = [
        ...round.matches.flatMap((m) => [...m.teamA, ...m.teamB]),
        ...round.sitters,
    ].sort((a, b) => a.nick.localeCompare(b.nick, 'cs'));

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    {round.number}. kolo
                    <Badge variant="outline">{round.format}</Badge>
                </CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4">
                <div className="grid gap-3 md:grid-cols-2">
                    {round.matches.map((match, index) => (
                        <MatchCard
                            key={match.id}
                            match={match}
                            showSeeds
                            seedDiff={seedDiffs[index]}
                        />
                    ))}
                </div>
                {round.sitters.length > 0 && (
                    <p className="text-sm">
                        <span className="text-muted-foreground">Sedí: </span>
                        {round.sitters
                            .map((p) => `${p.nick} (${p.seed})`)
                            .join(', ')}
                    </p>
                )}
                {editable && round.status === 'planned' && (
                    <SwapForm round={round} players={players} />
                )}
            </CardContent>
        </Card>
    );
}

function SwapForm({
    round,
    players,
}: {
    round: ScheduleRound;
    players: SchedulePlayer[];
}) {
    const [first, setFirst] = useState('');
    const [second, setSecond] = useState('');
    const [error, setError] = useState<string>();

    return (
        <div className="grid gap-2 border-t pt-3">
            <div className="flex flex-wrap items-center gap-2">
                <span className="text-sm text-muted-foreground">Prohodit</span>
                <select
                    aria-label="První hráč"
                    className={`${selectClass} sm:w-44`}
                    value={first}
                    onChange={(e) => setFirst(e.target.value)}
                >
                    <option value="">hráče…</option>
                    {players.map((p) => (
                        <option key={p.id} value={p.id}>
                            {p.nick}
                        </option>
                    ))}
                </select>
                <span className="text-sm text-muted-foreground">s</span>
                <select
                    aria-label="Druhý hráč"
                    className={`${selectClass} sm:w-44`}
                    value={second}
                    onChange={(e) => setSecond(e.target.value)}
                >
                    <option value="">hráčem…</option>
                    {players.map((p) => (
                        <option key={p.id} value={p.id}>
                            {p.nick}
                        </option>
                    ))}
                </select>
                <Button
                    size="sm"
                    variant="outline"
                    disabled={!first || !second}
                    onClick={() =>
                        router.visit(swap(round.id), {
                            data: {
                                first: Number(first),
                                second: Number(second),
                            },
                            preserveScroll: true,
                            onSuccess: () => {
                                setFirst('');
                                setSecond('');
                                setError(undefined);
                            },
                            onError: (errors) => setError(errors.players),
                        })
                    }
                >
                    Prohodit
                </Button>
            </div>
            <InputError message={error} />
        </div>
    );
}
