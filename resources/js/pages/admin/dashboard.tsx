import { Head, Link, router, usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import ConfirmDialog from '@/components/confirm-dialog';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { advance, revert } from '@/routes/admin/phase';
import { index as players } from '@/routes/admin/players';
import type { PhaseInfo } from '@/types';

type Props = {
    phases: PhaseInfo[];
    next: PhaseInfo | null;
    blockers: string[];
    revertTo: PhaseInfo | null;
    activePlayers: number;
    rankingsSubmitted: number | null;
};

export default function Dashboard({
    phases,
    next,
    blockers,
    revertTo,
    activePlayers,
    rankingsSubmitted,
}: Props) {
    const { phase } = usePage().props;
    const currentIndex = phases.findIndex((p) => p.value === phase.value);

    return (
        <>
            <Head title="Administrace" />
            <div className="grid gap-6 md:grid-cols-[2fr_1fr]">
                <Card>
                    <CardHeader>
                        <CardTitle>Fáze turnaje</CardTitle>
                        <CardDescription>
                            Přechody spouští jen admin a vrátit se lze jen před
                            začátkem hraní.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <ol className="grid gap-1">
                            {phases.map((p, index) => (
                                <li
                                    key={p.value}
                                    className={cn(
                                        'flex items-center gap-2 rounded-md px-2 py-1 text-sm',
                                        index === currentIndex &&
                                            'bg-primary font-medium text-primary-foreground',
                                        index > currentIndex &&
                                            'text-muted-foreground',
                                    )}
                                >
                                    <span className="w-4">
                                        {index < currentIndex && (
                                            <Check className="size-4" />
                                        )}
                                    </span>
                                    {p.label}
                                </li>
                            ))}
                        </ol>

                        {next && blockers.length > 0 && (
                            <Alert>
                                <AlertTitle>
                                    Do fáze „{next.label}“ zatím nelze přejít
                                </AlertTitle>
                                <AlertDescription>
                                    <ul className="list-disc pl-4">
                                        {blockers.map((blocker) => (
                                            <li key={blocker}>{blocker}</li>
                                        ))}
                                    </ul>
                                </AlertDescription>
                            </Alert>
                        )}

                        <div className="flex flex-wrap gap-2">
                            {next && (
                                <ConfirmDialog
                                    trigger={
                                        <Button disabled={blockers.length > 0}>
                                            Přejít do fáze: {next.label}
                                        </Button>
                                    }
                                    title={`Přejít do fáze „${next.label}“?`}
                                    description="Přechod uvidí všichni hráči."
                                    confirmLabel="Přejít"
                                    onConfirm={() => router.visit(advance())}
                                />
                            )}
                            {revertTo && (
                                <ConfirmDialog
                                    trigger={
                                        <Button variant="outline">
                                            Vrátit do fáze: {revertTo.label}
                                        </Button>
                                    }
                                    title={`Vrátit turnaj do fáze „${revertTo.label}“?`}
                                    confirmLabel="Vrátit"
                                    destructive
                                    onConfirm={() => router.visit(revert())}
                                />
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Hráči</CardTitle>
                        <CardDescription>
                            Turnaj potřebuje 10 až 16 aktivních hráčů.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <div className="text-4xl font-bold">
                            {activePlayers}
                        </div>
                        {rankingsSubmitted !== null && (
                            <p className="text-sm text-muted-foreground">
                                Hodnocení odeslalo {rankingsSubmitted} z{' '}
                                {activePlayers}.
                            </p>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={players()}>Správa hráčů</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
