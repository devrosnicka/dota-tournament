import { Head, router } from '@inertiajs/react';
import { ChevronDown, ChevronUp } from 'lucide-react';
import { useState } from 'react';
import { arrayMove } from '@dnd-kit/sortable';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    champion as storeChampion,
    qualification as storeQualification,
} from '@/routes/admin/tiebreaks';

type TiedPlayer = { id: number; nick: string };

type Props = {
    qualification: {
        players: TiedPlayer[];
        spots: number;
        resolved: boolean;
    } | null;
    champion: {
        bracket: TiedPlayer[];
        resolved: boolean;
        winner: number | null;
    } | null;
};

export default function Tiebreaks({ qualification, champion }: Props) {
    return (
        <>
            <Head title="Shody" />
            <div className="grid grid-cols-1 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            Postup do finále
                            {qualification && (
                                <Badge
                                    variant={
                                        qualification.resolved
                                            ? 'secondary'
                                            : 'destructive'
                                    }
                                >
                                    {qualification.resolved
                                        ? 'rozhodnuto'
                                        : 'čeká na rozstřel'}
                                </Badge>
                            )}
                        </CardTitle>
                        <CardDescription>
                            Shodu na hranici 10./11. místa rozhoduje rozstřel
                            1v1 se Shadow Fiendem (1v1 Solo Mid). Ostatní shody
                            rozhoduje nasazení.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {qualification ? (
                            <QualificationForm
                                key={qualification.players
                                    .map((p) => p.id)
                                    .join('-')}
                                qualification={qualification}
                            />
                        ) : (
                            <p className="text-muted-foreground">
                                Na hranici postupu teď žádná shoda není.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            Celkový šampion
                            {champion && (
                                <Badge
                                    variant={
                                        champion.resolved
                                            ? 'secondary'
                                            : 'destructive'
                                    }
                                >
                                    {champion.resolved
                                        ? 'rozhodnuto'
                                        : 'čeká na rozstřel'}
                                </Badge>
                            )}
                        </CardTitle>
                        <CardDescription>
                            Shodu o 1. místo rozhodují body z finále, pak
                            rozstřel 1v1 se Shadow Fiendem. Při třech a více
                            hráčích se hraje pavouk s nasazením losem.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {champion ? (
                            <ChampionForm champion={champion} />
                        ) : (
                            <p className="text-muted-foreground">
                                O 1. místo teď žádná shoda není (nebo ještě není
                                rozhodnutá série finále).
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function ChampionForm({
    champion,
}: {
    champion: NonNullable<Props['champion']>;
}) {
    const [winner, setWinner] = useState(champion.winner);
    const pairs: string[] = [];

    for (let i = 0; i < champion.bracket.length; i += 2) {
        const [a, b] = champion.bracket.slice(i, i + 2);
        pairs.push(
            b ? `${a.nick} vs ${b.nick}` : `${a.nick} postupuje bez boje`,
        );
    }

    return (
        <div className="grid gap-4">
            <div className="text-sm">
                <p className="font-medium">
                    {champion.bracket.length > 2
                        ? 'Pavouk (nasazení losem):'
                        : 'Rozstřel:'}
                </p>
                <ul className="list-disc pl-5">
                    {pairs.map((pair) => (
                        <li key={pair}>{pair}</li>
                    ))}
                </ul>
                {champion.bracket.length > 2 && (
                    <p className="text-muted-foreground">
                        Vítězové hrají dál, dokud nezbude jeden.
                    </p>
                )}
            </div>
            <div className="flex flex-wrap gap-2">
                {champion.bracket.map((player) => (
                    <Button
                        key={player.id}
                        variant={winner === player.id ? 'default' : 'outline'}
                        onClick={() => setWinner(player.id)}
                    >
                        {player.nick}
                    </Button>
                ))}
            </div>
            <Button
                disabled={winner === null}
                className="justify-self-start"
                onClick={() =>
                    router.visit(storeChampion(), {
                        data: { winner },
                        preserveScroll: true,
                    })
                }
            >
                Uložit vítěze rozstřelu
            </Button>
        </div>
    );
}

function QualificationForm({
    qualification,
}: {
    qualification: NonNullable<Props['qualification']>;
}) {
    const [order, setOrder] = useState(qualification.players);

    return (
        <div className="grid gap-4">
            <p className="text-sm">
                Seřaď hráče podle výsledku rozstřelu. Postupují první{' '}
                <strong>{qualification.spots}</strong>.
            </p>
            <ol className="grid gap-2">
                {order.map((player, index) => (
                    <li key={player.id} className="grid gap-2">
                        {index === qualification.spots && (
                            <div className="border-t-2 border-dashed border-primary/60" />
                        )}
                        <div className="flex h-11 items-center gap-2 rounded-lg border bg-card pr-1 pl-3">
                            <span className="w-6 text-sm text-muted-foreground">
                                {index + 1}.
                            </span>
                            <span className="flex-1 font-medium">
                                {player.nick}
                            </span>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label={`Posunout ${player.nick} výš`}
                                disabled={index === 0}
                                onClick={() =>
                                    setOrder(arrayMove(order, index, index - 1))
                                }
                            >
                                <ChevronUp />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label={`Posunout ${player.nick} níž`}
                                disabled={index === order.length - 1}
                                onClick={() =>
                                    setOrder(arrayMove(order, index, index + 1))
                                }
                            >
                                <ChevronDown />
                            </Button>
                        </div>
                    </li>
                ))}
            </ol>
            <Button
                onClick={() =>
                    router.visit(storeQualification(), {
                        data: { order: order.map((p) => p.id) },
                        preserveScroll: true,
                    })
                }
            >
                Uložit výsledek rozstřelu
            </Button>
        </div>
    );
}
