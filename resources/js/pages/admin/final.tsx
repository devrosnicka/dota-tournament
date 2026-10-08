import { Form, Head, router, usePage, usePoll } from '@inertiajs/react';
import ConfirmDialog from '@/components/confirm-dialog';
import FinalBoard from '@/components/final-board';
import InputError from '@/components/input-error';
import SeriesBoard from '@/components/series-board';
import { Alert, AlertDescription } from '@/components/ui/alert';
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
import { roleLabels, teamName } from '@/lib/final';
import { cn } from '@/lib/utils';
import { advantage, pick, role, undo } from '@/routes/admin/final';
import {
    destroy as destroyMap,
    store as storeMap,
} from '@/routes/admin/final/maps';
import type { FinalSide, FinalState } from '@/types';

type Props = {
    final: FinalState | null;
};

const options = { preserveScroll: true };

export default function AdminFinal({ final }: Props) {
    const { phase, errors } = usePage().props;

    usePoll(3000);

    if (!final) {
        return (
            <>
                <Head title="Finále" />
                <p className="text-muted-foreground">
                    Finále začne přechodem do fáze „Draft finále“.
                </p>
            </>
        );
    }

    return (
        <>
            <Head title="Finále" />
            <div className="grid grid-cols-1 gap-6">
                {errors.draft && (
                    <Alert variant="destructive">
                        <AlertDescription>{errors.draft}</AlertDescription>
                    </Alert>
                )}
                <Card>
                    <CardHeader>
                        <CardTitle>Draft</CardTitle>
                        <CardDescription>
                            Admin může udělat tah za kohokoli, kdo je na řadě, a
                            vrátit poslední tah.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <FinalBoard final={final} />
                        {phase.value === 'final_draft' && (
                            <DraftControls final={final} />
                        )}
                    </CardContent>
                </Card>

                {phase.value !== 'final_draft' && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Série Bo3</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <SeriesBoard final={final} />
                            {phase.value === 'final' &&
                                final.series.nextMap && (
                                    <MapForm final={final} />
                                )}
                            {phase.value === 'final' &&
                                final.series.maps.length > 0 && (
                                    <ConfirmDialog
                                        trigger={
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="justify-self-start"
                                            >
                                                Smazat poslední mapu
                                            </Button>
                                        }
                                        title="Smazat poslední zapsanou mapu?"
                                        confirmLabel="Smazat"
                                        destructive
                                        onConfirm={() =>
                                            router.visit(destroyMap(), options)
                                        }
                                    />
                                )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

function DraftControls({ final }: { final: FinalState }) {
    return (
        <div className="grid gap-4 border-t pt-4">
            {final.stage === 'advantage' && (
                <div className="flex flex-wrap gap-2">
                    <span className="w-full text-sm">
                        Výhoda za {final.captains.A.nick}:
                    </span>
                    <Button
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
                </div>
            )}

            {final.stage === 'picks' && final.pickingSide && (
                <div className="grid gap-2">
                    <span className="text-sm">
                        Výběr {final.picksMade + 1}/8 za{' '}
                        {final.captains[final.pickingSide].nick}:
                    </span>
                    <div className="flex flex-wrap gap-2">
                        {final.pool.map((player) => (
                            <Button
                                key={player.id}
                                size="sm"
                                variant="outline"
                                onClick={() =>
                                    router.visit(pick(), {
                                        data: { player: player.id },
                                        ...options,
                                    })
                                }
                            >
                                {player.nick}
                            </Button>
                        ))}
                    </div>
                </div>
            )}

            {final.stage === 'roles' && (
                <div className="grid gap-3 md:grid-cols-2">
                    {(['A', 'B'] as const).map((side) => (
                        <RoleControls key={side} final={final} side={side} />
                    ))}
                </div>
            )}

            {(final.stage !== 'advantage' || final.advantage) && (
                <ConfirmDialog
                    trigger={
                        <Button
                            variant="ghost"
                            size="sm"
                            className="justify-self-start"
                        >
                            Vrátit poslední tah
                        </Button>
                    }
                    title="Vrátit poslední tah draftu?"
                    confirmLabel="Vrátit"
                    onConfirm={() => router.visit(undo(), options)}
                />
            )}
        </div>
    );
}

function RoleControls({ final, side }: { final: FinalState; side: FinalSide }) {
    const playerId = final.roleTurn[side];
    const player = final.teams[side].find((p) => p.id === playerId);

    if (!player) {
        return (
            <p className="text-sm text-muted-foreground">
                {teamName(final, side)}: role hotové.
            </p>
        );
    }

    return (
        <div className="grid gap-2">
            <span className="text-sm">Role za {player.nick}:</span>
            <div className="flex flex-wrap gap-1">
                {final.freeRoles[side].map((position) => (
                    <Button
                        key={position}
                        size="sm"
                        variant="outline"
                        onClick={() =>
                            router.visit(role(), {
                                data: { player: player.id, role: position },
                                ...options,
                            })
                        }
                    >
                        {position} · {roleLabels[position]}
                    </Button>
                ))}
            </div>
        </div>
    );
}

function SideChoice({
    final,
    name,
    label,
    required = false,
}: {
    final: FinalState;
    name: string;
    label: string;
    required?: boolean;
}) {
    return (
        <fieldset className="grid gap-1">
            <legend className="mb-1 text-sm font-medium">{label}</legend>
            <div className="flex flex-wrap gap-2">
                {(['A', 'B'] as const).map((side) => (
                    <label
                        key={side}
                        className={cn(
                            'flex h-9 cursor-pointer items-center rounded-md border px-3 text-sm',
                            'has-[:checked]:border-primary has-[:checked]:bg-primary has-[:checked]:text-primary-foreground',
                        )}
                    >
                        <input
                            type="radio"
                            name={name}
                            value={side}
                            required={required}
                            className="sr-only"
                        />
                        {teamName(final, side)}
                    </label>
                ))}
            </div>
        </fieldset>
    );
}

function MapForm({ final }: { final: FinalState }) {
    return (
        <Form
            {...storeMap.form()}
            options={options}
            resetOnSuccess
            className="grid gap-4 border-t pt-4"
        >
            {({ processing, errors }) => (
                <>
                    <h3 className="font-medium">
                        {final.series.nextMap}. mapa
                    </h3>
                    <SideChoice
                        final={final}
                        name="winner"
                        label="Vítěz"
                        required
                    />
                    <div className="grid grid-cols-2 gap-2 sm:max-w-sm">
                        {(['a', 'b'] as const).map((side) => (
                            <div key={side} className="grid gap-1">
                                <Label htmlFor={`final_kills_${side}`}>
                                    Killy –{' '}
                                    {teamName(final, side === 'a' ? 'A' : 'B')}
                                </Label>
                                <Input
                                    id={`final_kills_${side}`}
                                    name={`kills_${side}`}
                                    type="number"
                                    min={0}
                                    max={300}
                                    placeholder="nepovinné"
                                />
                            </div>
                        ))}
                    </div>
                    <SideChoice
                        final={final}
                        name="radiant"
                        label="Radiant (nepovinné)"
                    />
                    <SideChoice
                        final={final}
                        name="first_pick"
                        label="První pick (nepovinné)"
                    />
                    <InputError message={errors.winner} />
                    <Button
                        type="submit"
                        disabled={processing}
                        className="justify-self-start"
                    >
                        Zapsat mapu
                    </Button>
                </>
            )}
        </Form>
    );
}
