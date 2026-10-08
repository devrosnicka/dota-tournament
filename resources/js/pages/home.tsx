import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Phase } from '@/types';

const phaseInfo: Record<Phase, string> = {
    registration:
        'Jsi zaregistrovaný. Až se sejdou všichni, admin spustí vzájemné hodnocení hráčů.',
    ranking: 'Probíhá vzájemné hodnocení hráčů.',
    schedule_review: 'Admin připravuje rozpis základní části.',
    group_stage: 'Hraje se základní část.',
    final_draft: 'Kapitáni vybírají týmy do finále.',
    final: 'Hraje se finále.',
    finished: 'Turnaj skončil.',
};

export default function Home() {
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
                    <CardContent className="text-muted-foreground">
                        {phaseInfo[phase.value]}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
