import { Head } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type SeedRow = {
    playerId: number;
    nick: string;
    rank: number;
    score: number;
    simpleMean: number;
    ratings: number;
};

type Props = {
    seeding: SeedRow[];
    snapshot: boolean;
    submitted: number;
};

const number = (value: number) =>
    value.toLocaleString('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

export default function Seeding({ seeding, snapshot, submitted }: Props) {
    return (
        <>
            <Head title="Nasazení" />
            <Card>
                <CardHeader>
                    <div className="flex items-center gap-2">
                        <CardTitle>Nasazení</CardTitle>
                        <Badge variant={snapshot ? 'default' : 'outline'}>
                            {snapshot ? 'Uzavřené' : 'Průběžné'}
                        </Badge>
                    </div>
                    <CardDescription>
                        Hodnocení odeslalo {submitted} z {seeding.length} hráčů.
                        Skóre je průměr pozic od ostatních (od 5 hodnocení bez
                        nejlepší a nejhorší), menší je silnější. Nasazení vidí
                        jen admin.
                    </CardDescription>
                </CardHeader>
                <CardContent className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="text-left text-muted-foreground">
                            <tr className="border-b">
                                <th className="py-2 pr-2 font-medium">#</th>
                                <th className="py-2 pr-2 font-medium">Hráč</th>
                                <th className="py-2 pr-2 text-right font-medium">
                                    Skóre
                                </th>
                                <th className="py-2 pr-2 text-right font-medium">
                                    Prostý průměr
                                </th>
                                <th className="py-2 text-right font-medium">
                                    Hodnocení
                                </th>
                            </tr>
                        </thead>
                        <tbody className="tabular-nums">
                            {seeding.map((row) => (
                                <tr
                                    key={row.playerId}
                                    className="border-b last:border-0"
                                >
                                    <td className="py-2 pr-2">{row.rank}.</td>
                                    <td className="py-2 pr-2 font-medium">
                                        {row.nick}
                                    </td>
                                    <td className="py-2 pr-2 text-right">
                                        {number(row.score)}
                                    </td>
                                    <td className="py-2 pr-2 text-right">
                                        {number(row.simpleMean)}
                                    </td>
                                    <td className="py-2 text-right">
                                        {row.ratings}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </>
    );
}
