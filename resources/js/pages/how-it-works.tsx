import { Head, Link, usePage } from '@inertiajs/react';
import TournamentFlow from '@/components/tournament-flow';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { roundsWord } from '@/lib/format';
import { login, register } from '@/routes';

type Props = {
    rounds: number;
    finalists: number;
    registrationOpen: boolean;
};

export default function HowItWorks({
    rounds,
    finalists,
    registrationOpen,
}: Props) {
    const { phase } = usePage().props;

    return (
        <>
            <Head title="Jak turnaj probíhá" />
            <div className="grid grid-cols-1 gap-4">
                <div className="grid gap-2">
                    <h1 className="text-2xl font-bold">Jak turnaj probíhá</h1>
                    <p className="leading-relaxed text-muted-foreground">
                        V základní části odehraješ {rounds} {roundsWord(rounds)}{' '}
                        v pokaždé jinak namíchaných týmech a body sbíráš sám za
                        sebe. {finalists} nejlepších se pak utká ve finále 5v5.
                    </p>
                </div>

                <Card>
                    <CardContent>
                        <TournamentFlow
                            current={phase.value}
                            rounds={rounds}
                            finalists={finalists}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="grid gap-3 text-center">
                        <p className="text-muted-foreground">
                            {registrationOpen
                                ? 'Registrace je otevřená. Po přihlášení uvidíš i podrobná pravidla a FAQ.'
                                : 'Po přihlášení uvidíš rozpis, tabulku a podrobná pravidla.'}
                        </p>
                        <div className="flex flex-wrap justify-center gap-2">
                            {registrationOpen && (
                                <Button asChild>
                                    <Link href={register()}>
                                        Zaregistrovat se
                                    </Link>
                                </Button>
                            )}
                            <Button
                                asChild
                                variant={
                                    registrationOpen ? 'outline' : 'default'
                                }
                            >
                                <Link href={login()}>Přihlásit se kódem</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
