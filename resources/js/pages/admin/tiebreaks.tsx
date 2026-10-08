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
import { qualification as storeQualification } from '@/routes/admin/tiebreaks';

type Props = {
    qualification: {
        players: { id: number; nick: string }[];
        spots: number;
        resolved: boolean;
    } | null;
};

export default function Tiebreaks({ qualification }: Props) {
    return (
        <>
            <Head title="Shody" />
            <div className="grid gap-6">
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
            </div>
        </>
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
