import { Head, router, usePage, usePoll } from '@inertiajs/react';
import ConfirmDialog from '@/components/confirm-dialog';
import FinalBoard from '@/components/final-board';
import SeriesBoard from '@/components/series-board';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { roleLabels, teamName } from '@/lib/final';
import { advantage, pick, role } from '@/routes/final';
import type { FinalState } from '@/types';

type Props = {
    final: FinalState | null;
};

export default function Final({ final }: Props) {
    const { phase, auth, errors } = usePage().props;

    usePoll(phase.value === 'final_draft' ? 2500 : 10_000);

    if (!final) {
        return (
            <>
                <Head title="Finále" />
                <h1 className="mb-4 text-2xl font-bold">Finále</h1>
                <p className="text-muted-foreground">Finále zatím nezačalo.</p>
            </>
        );
    }

    return (
        <>
            <Head title="Finále" />
            <div className="grid grid-cols-1 gap-4">
                <h1 className="text-2xl font-bold">Finále</h1>
                {errors.draft && (
                    <Alert variant="destructive">
                        <AlertDescription>{errors.draft}</AlertDescription>
                    </Alert>
                )}
                <StageCard final={final} />
                <Card>
                    <CardContent>
                        <FinalBoard
                            final={final}
                            highlightId={auth.player?.id}
                        />
                    </CardContent>
                </Card>
                {(final.stage === 'done' || final.series.maps.length > 0) && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Série Bo3</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <SeriesBoard final={final} />
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

function StageCard({ final }: { final: FinalState }) {
    const { you } = final;
    const options = { preserveScroll: true };

    if (final.stage === 'advantage') {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Volba výhody</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-3">
                    {you.canChooseAdvantage ? (
                        <>
                            <p className="text-sm text-muted-foreground">
                                Jako vítěz základní části si vyber výhodu. Druhý
                                kapitán dostane tu druhou.
                            </p>
                            <Button
                                className="h-12"
                                onClick={() =>
                                    router.visit(advantage(), {
                                        data: { advantage: 'player_pick' },
                                        ...options,
                                    })
                                }
                            >
                                První výběr hráče
                            </Button>
                            <Button
                                className="h-12"
                                variant="outline"
                                onClick={() =>
                                    router.visit(advantage(), {
                                        data: { advantage: 'side_pick' },
                                        ...options,
                                    })
                                }
                            >
                                Strana a první pick na 1. mapě
                            </Button>
                        </>
                    ) : (
                        <p className="text-muted-foreground">
                            Čeká se, až {final.captains.A.nick} zvolí výhodu.
                        </p>
                    )}
                </CardContent>
            </Card>
        );
    }

    if (final.stage === 'picks' && final.pickingSide) {
        const captain = final.captains[final.pickingSide];

        return (
            <Card>
                <CardHeader>
                    <CardTitle>
                        Výběr hráčů {final.picksMade + 1}/8 · vybírá{' '}
                        {captain.nick}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {you.canPick ? (
                        <ul className="grid gap-2">
                            {final.pool.map((player) => (
                                <li key={player.id}>
                                    <ConfirmDialog
                                        trigger={
                                            <Button
                                                variant="outline"
                                                className="h-12 w-full justify-between"
                                            >
                                                {player.nick}
                                                <span className="text-xs text-muted-foreground">
                                                    {player.placement}. místo
                                                </span>
                                            </Button>
                                        }
                                        title={`Vybrat hráče ${player.nick}?`}
                                        confirmLabel="Vybrat"
                                        onConfirm={() =>
                                            router.visit(pick(), {
                                                data: { player: player.id },
                                                ...options,
                                            })
                                        }
                                    />
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-muted-foreground">
                            Na tahu je {captain.nick}.
                        </p>
                    )}
                </CardContent>
            </Card>
        );
    }

    if (final.stage === 'roles') {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Volba rolí</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-3">
                    {you.canChooseRole && you.side ? (
                        <>
                            <p className="text-sm">
                                Jsi na řadě, zvol si pozici:
                            </p>
                            <div className="grid gap-2">
                                {final.freeRoles[you.side].map((position) => (
                                    <Button
                                        key={position}
                                        className="h-12 justify-start"
                                        variant="outline"
                                        onClick={() =>
                                            router.visit(role(), {
                                                data: { role: position },
                                                ...options,
                                            })
                                        }
                                    >
                                        {position} · {roleLabels[position]}
                                    </Button>
                                ))}
                            </div>
                        </>
                    ) : (
                        <p className="text-muted-foreground">
                            Hráči si volí pozice podle umístění v základní
                            části.
                            {you.side && final.roleTurn[you.side] && (
                                <>
                                    {' '}
                                    V tvém týmu je na řadě{' '}
                                    {
                                        final.teams[you.side].find(
                                            (p) =>
                                                p.id ===
                                                final.roleTurn[you.side!],
                                        )?.nick
                                    }
                                    .
                                </>
                            )}
                        </p>
                    )}
                </CardContent>
            </Card>
        );
    }

    return you.side ? (
        <p className="text-muted-foreground">
            Hraješ za{' '}
            <strong className="text-foreground">
                {teamName(final, you.side)}
            </strong>
            .
        </p>
    ) : null;
}
