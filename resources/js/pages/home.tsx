import { Head, Link, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ranking as rankingPage } from '@/routes';
import type { Phase } from '@/types';

const phaseInfo: Record<Phase, string> = {
    registration:
        'Jsi zaregistrovaný. Až se sejdou všichni, admin spustí vzájemné hodnocení hráčů.',
    ranking:
        'Seřaď ostatní hráče od nejlepšího po nejhoršího. Z hodnocení všech vznikne nasazení pro vyvážené týmy.',
    schedule_review: 'Admin připravuje rozpis základní části.',
    group_stage: 'Hraje se základní část.',
    final_draft: 'Kapitáni vybírají týmy do finále.',
    final: 'Hraje se finále.',
    finished: 'Turnaj skončil.',
};

type Props = {
    ranking: { submitted: boolean } | null;
};

export default function Home({ ranking }: Props) {
    const { phase, auth } = usePage().props;

    return (
        <>
            <Head title="Domů" />
            <div className="grid gap-4">
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
            </div>
        </>
    );
}
