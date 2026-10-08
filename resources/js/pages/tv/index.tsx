import { Head, usePage, usePoll } from '@inertiajs/react';
import MatchCard from '@/components/match-card';
import StandingsTable from '@/components/standings-table';
import type { GroupStandings, ScheduleRound } from '@/types';

type Props = {
    players?: string[];
    ranking?: { submitted: number; total: number };
    round?: ScheduleRound | null;
    standings?: GroupStandings;
};

export default function Tv({ players, ranking, round, standings }: Props) {
    const { phase } = usePage().props;

    usePoll(10_000);

    return (
        <>
            <Head title="TV" />
            {players && (
                <section>
                    <h2 className="mb-6 text-3xl text-muted-foreground">
                        Přihlášení hráči: {players.length}
                    </h2>
                    <ul className="flex flex-wrap gap-4">
                        {players.map((nick) => (
                            <li
                                key={nick}
                                className="rounded-xl bg-card px-6 py-3 text-4xl font-semibold"
                            >
                                {nick}
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {ranking && (
                <section className="flex flex-col items-center gap-8 pt-20">
                    <p className="text-4xl text-muted-foreground">
                        Hodnocení odeslalo
                    </p>
                    <p className="text-[10rem] leading-none font-bold tabular-nums">
                        {ranking.submitted} / {ranking.total}
                    </p>
                    <div className="h-6 w-2/3 overflow-hidden rounded-full bg-muted">
                        <div
                            className="h-full bg-primary transition-all"
                            style={{
                                width: `${(100 * ranking.submitted) / Math.max(1, ranking.total)}%`,
                            }}
                        />
                    </div>
                </section>
            )}

            {phase.value === 'schedule_review' && (
                <p className="pt-20 text-center text-5xl text-muted-foreground">
                    Připravuje se rozpis…
                </p>
            )}

            {standings && (
                <div className="grid gap-10 xl:grid-cols-[3fr_2fr]">
                    <section>
                        <h2 className="mb-4 text-4xl font-semibold">
                            {round
                                ? `${round.number}. kolo · ${round.format}`
                                : 'Základní část odehrána'}
                        </h2>
                        {round && (
                            <div className="grid gap-6">
                                {round.matches.map((match) => (
                                    <MatchCard
                                        key={match.id}
                                        match={match}
                                        size="tv"
                                    />
                                ))}
                                {round.sitters.length > 0 && (
                                    <p className="text-3xl">
                                        <span className="text-muted-foreground">
                                            Sedí:{' '}
                                        </span>
                                        {round.sitters
                                            .map((p) => p.nick)
                                            .join(', ')}
                                    </p>
                                )}
                            </div>
                        )}
                    </section>
                    <section>
                        <h2 className="mb-4 text-4xl font-semibold">Tabulka</h2>
                        <StandingsTable standings={standings} size="tv" />
                    </section>
                </div>
            )}
        </>
    );
}
