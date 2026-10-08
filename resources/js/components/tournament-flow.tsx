import {
    ArrowDown,
    CalendarDays,
    Check,
    Crown,
    EyeOff,
    GripVertical,
    ListOrdered,
    Medal,
    Shuffle,
    Swords,
    Trophy,
    UserPlus,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { roundsWord } from '@/lib/format';
import { isAtLeast } from '@/lib/phase';
import { cn } from '@/lib/utils';
import type { Phase } from '@/types';

type Props = {
    /** The phase the tournament is in now. */
    current: Phase;
    rounds: number;
    finalists: number;
    /** Ids of rules sections with the details of a phase, shown as links. */
    details?: Partial<Record<Phase, string>>;
};

type Step = {
    value: Phase;
    label: string;
    icon: LucideIcon;
    text: string;
    visual: ReactNode;
};

/** Pick order of the final draft, A is the captain with the first pick. */
const snake = ['A', 'B', 'B', 'A', 'A', 'B', 'B', 'A'] as const;

/**
 * The phases of the tournament as a timeline, with a small picture of each
 * phase and the current one highlighted.
 */
export default function TournamentFlow({
    current,
    rounds,
    finalists,
    details = {},
}: Props) {
    const steps: Step[] = [
        {
            value: 'registration',
            label: 'Registrace',
            icon: UserPlus,
            text: 'Zaregistruješ se přezdívkou a kódem, který znají jen účastníci.',
            visual: (
                <div className="flex flex-wrap gap-1.5">
                    {['Puck', 'Lina', 'Axe'].map((nick) => (
                        <Chip key={nick}>{nick}</Chip>
                    ))}
                    <Chip className="border-dashed text-muted-foreground">
                        + ty
                    </Chip>
                </div>
            ),
        },
        {
            value: 'ranking',
            label: 'Hodnocení hráčů',
            icon: ListOrdered,
            text: 'Seřadíš ostatní od nejlepšího po nejhoršího. Z hodnocení všech vznikne nasazení pro vyrovnané týmy.',
            visual: (
                <div className="flex flex-wrap items-center gap-3">
                    <ol className="grid w-32 gap-1">
                        {['Lina', 'Axe', 'Puck'].map((nick, index) => (
                            <li
                                key={nick}
                                className="flex items-center gap-1.5 rounded-md border bg-background px-2 py-0.5 text-xs"
                            >
                                <span className="text-muted-foreground tabular-nums">
                                    {index + 1}.
                                </span>
                                <span className="flex-1">{nick}</span>
                                <GripVertical className="size-3 text-muted-foreground" />
                            </li>
                        ))}
                    </ol>
                    <span className="flex items-center gap-1 text-xs text-muted-foreground">
                        <EyeOff className="size-3.5" />
                        anonymně
                    </span>
                </div>
            ),
        },
        {
            value: 'schedule_review',
            label: 'Příprava rozpisu',
            icon: CalendarDays,
            text: 'Admin vygeneruje rozpis všech kol najednou a zveřejní ho.',
            visual: (
                <div className="flex flex-wrap gap-1.5">
                    {Array.from({ length: rounds }, (_, index) => (
                        <div
                            key={index}
                            className="flex w-9 flex-col items-center rounded-md border bg-background py-0.5"
                        >
                            <span className="text-[10px] text-muted-foreground">
                                kolo
                            </span>
                            <span className="text-sm font-semibold">
                                {index + 1}
                            </span>
                        </div>
                    ))}
                </div>
            ),
        },
        {
            value: 'group_stage',
            label: 'Základní část',
            icon: Shuffle,
            text: `Odehraješ ${rounds} ${roundsWord(rounds)}, pokaždé v jinak namíchaném týmu. Body sbíráš sám za sebe.`,
            visual: (
                <div className="flex flex-wrap gap-1.5">
                    <Chip className="border-primary bg-primary font-semibold text-primary-foreground">
                        výhra +1
                    </Chip>
                    <Chip>sezení +0,5</Chip>
                    <Chip className="text-muted-foreground">prohra 0</Chip>
                </div>
            ),
        },
        {
            value: 'final_draft',
            label: 'Draft finále',
            icon: Users,
            text: `Postupuje ${finalists} nejlepších. První dva jsou kapitáni a hadově si vybírají spoluhráče.`,
            visual: (
                <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                    <ol aria-label="Pořadí výběru" className="flex gap-1">
                        {snake.map((side, index) => (
                            <li
                                key={index}
                                className={cn(
                                    'flex size-6 items-center justify-center rounded-md border text-xs font-semibold',
                                    side === 'A'
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'bg-background',
                                )}
                            >
                                {side}
                            </li>
                        ))}
                    </ol>
                    <span className="text-xs text-muted-foreground">
                        pořadí výběru
                    </span>
                </div>
            ),
        },
        {
            value: 'final',
            label: 'Finále',
            icon: Swords,
            text: 'Tým proti týmu 5v5 na dvě vítězné mapy (Bo3).',
            visual: (
                <div className="flex flex-wrap gap-1.5">
                    <Chip>Mapa 1</Chip>
                    <Chip>Mapa 2</Chip>
                    <Chip className="border-dashed text-muted-foreground">
                        Mapa 3 jen za stavu 1:1
                    </Chip>
                </div>
            ),
        },
        {
            value: 'finished',
            label: 'Vyhlášení',
            icon: Medal,
            text: 'Trofeje pro vítězný tým finále a pro celkového šampiona.',
            visual: (
                <div className="flex flex-wrap gap-1.5">
                    <Chip>
                        <Trophy className="size-3.5 text-amber-500" />
                        Vítězný tým
                    </Chip>
                    <Chip>
                        <Crown className="size-3.5 text-amber-500" />
                        Celkový šampion
                    </Chip>
                </div>
            ),
        },
    ];

    return (
        <ol className="grid">
            {steps.map((step, index) => {
                const reached = isAtLeast(current, step.value);
                const now = step.value === current;
                const next = steps[index + 1];
                const detail = details[step.value];

                return (
                    <li
                        key={step.value}
                        aria-current={now ? 'step' : undefined}
                        className="relative flex gap-3 pb-4 last:pb-0"
                    >
                        {next && (
                            <span
                                aria-hidden
                                className={cn(
                                    'absolute top-10 bottom-0 left-[19px] border-l-2',
                                    isAtLeast(current, next.value)
                                        ? 'border-primary'
                                        : 'border-dashed',
                                )}
                            />
                        )}
                        <span
                            className={cn(
                                'relative flex size-10 shrink-0 items-center justify-center rounded-full border-2',
                                now
                                    ? 'border-primary bg-primary text-primary-foreground ring-4 ring-primary/20'
                                    : reached
                                      ? 'border-primary bg-background text-primary'
                                      : 'bg-background text-muted-foreground',
                            )}
                        >
                            <step.icon className="size-5" />
                            {reached && !now && (
                                <span className="absolute -right-1 -bottom-1 flex size-4 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                    <Check className="size-3" />
                                </span>
                            )}
                        </span>
                        <div
                            className={cn(
                                'grid min-w-0 flex-1 gap-2 rounded-lg px-3 py-2',
                                now && 'bg-primary/5 ring-1 ring-primary',
                            )}
                        >
                            <div className="grid gap-0.5">
                                <h3 className="flex flex-wrap items-center gap-x-2 leading-6 font-semibold">
                                    {step.label}
                                    {now && (
                                        <span className="inline-flex items-center gap-1.5 rounded-full bg-primary px-2 text-xs leading-5 font-medium text-primary-foreground">
                                            <span className="size-1.5 rounded-full bg-current motion-safe:animate-pulse" />
                                            právě teď
                                        </span>
                                    )}
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {step.text}
                                </p>
                            </div>
                            {step.visual}
                            {detail && (
                                <a
                                    href={`#${detail}`}
                                    className="inline-flex items-center gap-1 justify-self-start text-xs underline-offset-4 hover:underline"
                                >
                                    Podrobnosti
                                    <ArrowDown className="size-3" />
                                </a>
                            )}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}

function Chip({
    className,
    children,
}: {
    className?: string;
    children: ReactNode;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-md border bg-background px-2 py-0.5 text-xs',
                className,
            )}
        >
            {children}
        </span>
    );
}
