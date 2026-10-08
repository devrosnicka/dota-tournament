import { Head, usePage, usePoll } from '@inertiajs/react';
import FinalBoard from '@/components/final-board';
import MatchCard from '@/components/match-card';
import SeriesBoard from '@/components/series-board';
import StandingsTable from '@/components/standings-table';
import { teamName } from '@/lib/final';
import type { FinalState, GroupStandings, ScheduleRound } from '@/types';

type Props = {
    players?: string[];
    ranking?: { submitted: number; total: number };
    round?: ScheduleRound | null;
    standings?: GroupStandings;
    final?: FinalState | null;
};

export default function Tv({
    players,
    ranking,
    round,
    standings,
    final,
}: Props) {
    const { phase } = usePage().props;

    usePoll(phase.value === 'final_draft' ? 2500 : 10_000);

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

            {final && <FinalSection final={final} />}
        </>
    );
}

function FinalSection({ final }: { final: FinalState }) {
    const status = (() => {
        if (final.stage === 'advantage') {
            return `${final.captains.A.nick} volí výhodu`;
        }

        if (final.stage === 'picks' && final.pickingSide) {
            return `Výběr ${final.picksMade + 1}/8 · vybírá ${final.captains[final.pickingSide].nick}`;
        }

        if (final.stage === 'roles') {
            return 'Volba rolí';
        }

        return null;
    })();

    return (
        <div className="grid gap-10">
            {status && <p className="text-5xl font-semibold">{status}</p>}
            {final.stage !== 'done' && final.advantage && (
                <p className="text-3xl text-muted-foreground">
                    {teamName(final, final.firstPickSide ?? 'A')} má první výběr
                    hráče, {teamName(final, final.series.sidePickHolder ?? 'B')}{' '}
                    stranu a první pick na 1. mapě.
                </p>
            )}
            <FinalBoard final={final} size="tv" />
            {final.stage === 'done' && <SeriesBoard final={final} size="tv" />}
        </div>
    );
}
