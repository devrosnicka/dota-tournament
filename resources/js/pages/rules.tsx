import { Head, usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { MouseEvent, ReactNode } from 'react';
import TournamentFlow from '@/components/tournament-flow';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { roundsWord } from '@/lib/format';
import { cn } from '@/lib/utils';

type Props = {
    rounds: number;
    players: number;
    format: { name: string; matches: number; sitting: number } | null;
    finalists: number;
};

const formats = [
    { players: 10, format: '1× 5v5', sitting: 0 },
    { players: 11, format: '1× 5v5', sitting: 1 },
    { players: 12, format: '2× 3v3', sitting: 0 },
    { players: 13, format: '2× 3v3', sitting: 1 },
    { players: 14, format: '2× 3v3', sitting: 2 },
    { players: 15, format: '2× 3v3', sitting: 3 },
    { players: 16, format: '2× 4v4', sitting: 0 },
];

const sections = [
    ['prubeh', 'Průběh'],
    ['hodnoceni', 'Hodnocení'],
    ['zakladni-cast', 'Základní část'],
    ['tabulka', 'Tabulka a shody'],
    ['finale', 'Finále'],
    ['celkove-poradi', 'Celkové pořadí'],
    ['faq', 'FAQ'],
] as const;

export default function Rules({ rounds, players, format, finalists }: Props) {
    const { phase } = usePage().props;

    return (
        <>
            <Head title="Pravidla" />
            <div className="grid grid-cols-1 gap-4">
                <h1 className="text-2xl font-bold">Pravidla turnaje</h1>
                <SectionNav />

                <Section id="prubeh" title="Průběh turnaje">
                    <TournamentFlow
                        current={phase.value}
                        rounds={rounds}
                        finalists={finalists}
                        details={{
                            ranking: 'hodnoceni',
                            schedule_review: 'zakladni-cast',
                            group_stage: 'zakladni-cast',
                            final_draft: 'finale',
                            final: 'finale',
                            finished: 'celkove-poradi',
                        }}
                    />
                </Section>

                <Section id="hodnoceni" title="Hodnocení hráčů a nasazení">
                    <p>
                        Každý seřadí všechny ostatní hráče od nejlepšího po
                        nejhoršího, sebe nehodnotí. Pořadí můžeš měnit, dokud
                        admin hodnocení neuzavře. Odeslání není povinné, ale čím
                        víc lidí hodnotí, tím vyrovnanější budou týmy.
                    </p>
                    <p>
                        Z pozic, které ti dali ostatní, se spočítá průměr. Od
                        pěti hodnocení se nejlepší a nejhorší pozice nepočítá,
                        aby jeden výkyv nic nezkreslil. Podle průměru vznikne{' '}
                        <strong>nasazení</strong>, které slouží k vyvažování
                        týmů a jako poslední kritérium při shodě v tabulce.
                    </p>
                    <p>
                        <strong>Hodnocení je anonymní.</strong> Svoje pořadí
                        vidíš jen ty, cizí neuvidí nikdo. Admin vidí jen
                        výsledné nasazení, ne kdo jak hodnotil.
                    </p>
                </Section>

                <Section id="zakladni-cast" title="Základní část">
                    <p>
                        Hraje se{' '}
                        <strong>
                            {rounds} {roundsWord(rounds)}
                        </strong>
                        . Formát kola záleží na počtu hráčů:
                    </p>
                    <table className="w-full text-sm">
                        <thead className="text-left text-muted-foreground">
                            <tr className="border-b">
                                <th className="py-1 font-medium">Hráčů</th>
                                <th className="py-1 font-medium">
                                    Zápasy v kole
                                </th>
                                <th className="py-1 font-medium">Sedí</th>
                            </tr>
                        </thead>
                        <tbody>
                            {formats.map((row) => (
                                <tr
                                    key={row.players}
                                    className={cn(
                                        'border-b last:border-0',
                                        row.players === players &&
                                            'bg-primary/10 font-semibold',
                                    )}
                                >
                                    <td className="py-1">{row.players}</td>
                                    <td className="py-1">{row.format}</td>
                                    <td className="py-1">{row.sitting}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {format && (
                        <p className="text-sm text-muted-foreground">
                            Teď je aktivních {players} hráčů, takže se hraje{' '}
                            {format.matches}× {format.name}
                            {format.sitting > 0
                                ? ` a v každém kole sedí ${format.sitting}`
                                : ''}
                            .
                        </p>
                    )}
                    <ul className="list-disc space-y-1 pl-5">
                        <li>
                            <strong>Body:</strong> výhra 1, prohra 0, sezení
                            0,5.
                        </li>
                        <li>
                            <strong>Týmy</strong> se každé kolo mění. Generátor
                            hlídá, abys nehrál pořád se stejnými lidmi a aby
                            zápasy nebyly jednostranné. Přesně vyrovnané ale
                            schválně nejsou.
                        </li>
                        <li>
                            <strong>Sezení</strong> se rozděluje rovnoměrně:
                            nikdo nesedí podruhé, dokud všichni neseděli aspoň
                            jednou, a pokud to jde, nikdo nesedí dvě kola po
                            sobě. Kdo sedí, určí los, ne nasazení.
                        </li>
                        <li>
                            <strong>Výsledek</strong> zadává kdokoli z hráčů
                            zápasu: kdo vyhrál a killy obou týmů podle skóre ve
                            hře. Dokud admin kolo neuzavře, jde výsledek
                            opravit.
                        </li>
                        <li>
                            <strong>Rozdíl killů</strong> je součet (killy tvého
                            týmu − killy soupeře) přes všechny tvoje zápasy. Do
                            bodů se nepočítá, rozhoduje jen při shodě bodů.
                        </li>
                    </ul>
                </Section>

                <Section id="tabulka" title="Tabulka a shody">
                    <p>Pořadí v tabulce určuje:</p>
                    <ol className="list-decimal space-y-1 pl-5">
                        <li>
                            <strong>body</strong>,
                        </li>
                        <li>
                            při shodě bodů <strong>rozdíl killů</strong>,
                        </li>
                        <li>
                            při shodě i v killech <strong>nasazení</strong>{' '}
                            (lepší nasazení je výš).
                        </li>
                    </ol>
                    <p>
                        Do finále postupuje{' '}
                        <strong>{finalists} nejlepších</strong> aktivních hráčů.
                        Kdo odstoupil, zůstává v tabulce se svými body, ale do
                        pořadí pro postup se nepočítá.
                    </p>
                    <p>
                        Jediná výjimka: když se přímo na hranici postupu (10. a
                        11. místo) shodují body i rozdíl killů, nerozhodne
                        nasazení, ale <strong>rozstřel 1v1</strong> se Shadow
                        Fiendem v módu 1v1 Solo Mid. Rozstřel určí, kdo
                        postupuje, a pořadí mezi postupujícími dál řídí
                        nasazení.
                    </p>
                </Section>

                <Section id="finale" title="Finále">
                    <ol className="list-decimal space-y-1 pl-5">
                        <li>
                            <strong>Kapitáni</strong> jsou 1. a 2. místo
                            základní části.
                        </li>
                        <li>
                            <strong>Výhoda:</strong> kapitán z 1. místa si
                            vybere buď první výběr hráče, nebo stranu a první
                            pick na 1. mapě. Druhý kapitán dostane to druhé.
                        </li>
                        <li>
                            <strong>Výběr hráčů</strong> probíhá hadově: A, B,
                            B, A, A, B, B, A (A = kapitán s prvním výběrem).
                            Kapitáni vybírají na mobilu, ostatní to sledují na
                            TV.
                        </li>
                        <li>
                            <strong>Role:</strong> v každém týmu si hráči včetně
                            kapitána volí pozici 1–5 v pořadí podle umístění v
                            základní části. Každá pozice je v týmu jen jednou.
                        </li>
                        <li>
                            <strong>Série Bo3:</strong> 3. mapa se hraje jen za
                            stavu 1:1. Před 2. a 3. mapou si tým, který prohrál
                            předchozí mapu, vybere stranu, nebo první pick, a
                            druhý tým dostane to druhé.
                        </li>
                        <li>
                            <strong>Body z finále:</strong> +1 za každou mapu
                            vyhranou tvým týmem a +1 za výhru v sérii. Za 2:0
                            tedy vítězové dostanou 3 body a poražení 0, za 2:1
                            vítězové 3 a poražení 1.
                        </li>
                    </ol>
                </Section>

                <Section id="celkove-poradi" title="Celkové pořadí a trofeje">
                    <p>
                        Celkové body = body ze základní části + body z finále.
                        Hrají se dvě trofeje:
                    </p>
                    <ul className="list-disc space-y-1 pl-5">
                        <li>
                            <strong>Vítězný tým</strong> – vítězové finále.
                        </li>
                        <li>
                            <strong>Celkový šampion</strong> – 1. místo
                            celkového pořadí. Při shodě rozhodují body z finále,
                            pak rozstřel 1v1 se Shadow Fiendem (při třech a více
                            hráčích pavouk s nasazením losem).
                        </li>
                    </ul>
                    <p className="text-sm text-muted-foreground">
                        Ostatní shody v celkovém pořadí jsou sdílené.
                    </p>
                </Section>

                <Section id="faq" title="Časté otázky">
                    <div className="grid gap-2">
                        <Faq question="Jak se přihlásím na jiném zařízení?">
                            Na zařízení, kde jsi přihlášený, otevři Menu →
                            Přihlásit jiné zařízení a vygeneruj kód. Na druhém
                            zařízení ho zadej na přihlašovací stránce. Kód platí
                            15 minut a jde použít jen jednou. Když přihlášení
                            nemáš nikde, kód ti vygeneruje admin.
                        </Faq>
                        <Faq question="Otevřel jsem stránku v Messengeru a v prohlížeči nejsem přihlášený.">
                            Aplikace v Messengeru nebo WhatsAppu mají vlastní
                            prohlížeč. Vygeneruj si v nich kód pro jiné zařízení
                            a přihlas se jím v normálním prohlížeči.
                        </Faq>
                        <Faq question="Uvidí někdo, jak jsem koho ohodnotil?">
                            Ne. Svoje pořadí vidíš jen ty. Admin vidí jen
                            výsledné nasazení všech hráčů, ne jednotlivá
                            hodnocení.
                        </Faq>
                        <Faq question="Musím hodnocení odeslat?">
                            Nemusíš, ale pomůže to vyrovnanějším týmům. Dokud
                            admin hodnocení neuzavře, můžeš pořadí měnit a
                            ukládat znovu.
                        </Faq>
                        <Faq question="Kde zjistím, s kým a kdy hraju?">
                            Na úvodní stránce vidíš svůj zápas v aktuálním kole
                            a v Rozpisu všechna kola. Tvoje zápasy a sezení jsou
                            zvýrazněné.
                        </Faq>
                        <Faq question="Kdo zadává výsledek a co když se spletu?">
                            Kdokoli z hráčů zápasu, stačí jeden. Dokud admin
                            kolo neuzavře, můžeš výsledek opravit sám, potom
                            řekni adminovi. Každý zápis se ukládá i s tím, kdo
                            ho zadal.
                        </Faq>
                        <Faq question="Kdy začne zápas?">
                            Až jsou u počítačů všichni jeho hráči. Pozdní
                            příchody se neřeší, zápas prostě počká.
                        </Faq>
                        <Faq question="Co dostanu, když sedím?">
                            0,5 bodu. Sezení se rozděluje rovnoměrně a losem,
                            takže nikoho nezvýhodní ani neznevýhodní.
                        </Faq>
                        <Faq question="Musím odejít dřív. Co se stane?">
                            Dej vědět adminovi. Získané body ti zůstanou, ale do
                            finále už nepostoupíš. Zbývající neodehraná kola se
                            přegenerují pro ostatní.
                        </Faq>
                        <Faq question="Proč nejsou týmy úplně vyrovnané?">
                            Schválně. Generátor jen hlídá, aby zápasy nebyly
                            jednostranné. Kdyby byly týmy přesně vyrovnané,
                            rozhodovala by o tabulce hlavně náhoda.
                        </Faq>
                    </div>
                </Section>
            </div>
        </>
    );
}

/** Distance from the top of the viewport where a section counts as current. */
const SPY_OFFSET = 140;

/**
 * Section links that stay under the header while scrolling and highlight the
 * section being read.
 */
function SectionNav() {
    const [active, setActive] = useState<string>(sections[0][0]);
    const listRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        let frame = 0;

        const update = () => {
            frame = 0;
            let current: string = sections[0][0];

            for (const [id] of sections) {
                const top = document
                    .getElementById(id)
                    ?.getBoundingClientRect().top;

                if (top !== undefined && top <= SPY_OFFSET) {
                    current = id;
                }
            }

            const atBottom =
                window.innerHeight + window.scrollY >=
                document.documentElement.scrollHeight - 2;

            setActive(atBottom ? sections[sections.length - 1][0] : current);
        };

        const schedule = () => {
            if (!frame) {
                frame = requestAnimationFrame(update);
            }
        };

        update();
        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);

        return () => {
            window.removeEventListener('scroll', schedule);
            window.removeEventListener('resize', schedule);
            cancelAnimationFrame(frame);
        };
    }, []);

    // Keep the highlighted link visible in the horizontally scrolling list.
    useEffect(() => {
        const list = listRef.current;
        const link = list?.querySelector<HTMLElement>(
            `[data-section="${active}"]`,
        );

        if (list && link) {
            list.scrollTo({
                left:
                    link.offsetLeft - (list.clientWidth - link.clientWidth) / 2,
                behavior: 'smooth',
            });
        }
    }, [active]);

    function jump(event: MouseEvent<HTMLAnchorElement>, id: string) {
        event.preventDefault();
        document
            .getElementById(id)
            ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        history.replaceState(null, '', `#${id}`);
        setActive(id);
    }

    return (
        <nav
            aria-label="Sekce pravidel"
            className="sticky top-14 z-[5] -mx-4 border-b bg-background/95 px-4 py-2 backdrop-blur"
        >
            <div
                ref={listRef}
                className="flex [scrollbar-width:none] gap-2 overflow-x-auto text-sm [&::-webkit-scrollbar]:hidden"
            >
                {sections.map(([id, label]) => (
                    <a
                        key={id}
                        href={`#${id}`}
                        data-section={id}
                        aria-current={active === id ? 'location' : undefined}
                        onClick={(event) => jump(event, id)}
                        className={cn(
                            'shrink-0 rounded-full border px-3 py-1 whitespace-nowrap transition-colors',
                            active === id
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:bg-accent',
                        )}
                    >
                        {label}
                    </a>
                ))}
            </div>
        </nav>
    );
}

function Section({
    id,
    title,
    children,
}: {
    id: string;
    title: string;
    children: ReactNode;
}) {
    return (
        <Card id={id} className="scroll-mt-32">
            <CardHeader>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-3 leading-relaxed">
                {children}
            </CardContent>
        </Card>
    );
}

function Faq({
    question,
    children,
}: {
    question: string;
    children: ReactNode;
}) {
    return (
        <details className="group rounded-md border px-3 py-2">
            <summary className="flex cursor-pointer list-none items-center justify-between gap-2 font-medium">
                {question}
                <ChevronDown className="size-4 shrink-0 transition-transform group-open:rotate-180" />
            </summary>
            <p className="mt-2 text-muted-foreground">{children}</p>
        </details>
    );
}
