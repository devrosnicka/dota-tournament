import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Brand, { PhaseBadge } from '@/components/brand';

/**
 * Projector view: large type, no navigation.
 */
export default function TvLayout({ children }: { children: ReactNode }) {
    const { name, phase } = usePage().props;

    return (
        <div className="min-h-svh text-foreground">
            <header className="hud-line mb-6 flex items-center justify-between px-10 pt-8 pb-5">
                <h1>
                    <Brand name={name} className="text-4xl" />
                </h1>
                <PhaseBadge
                    label={phase.label}
                    className="gap-3 px-4 py-1.5 text-2xl [&>span]:size-3"
                />
            </header>
            <main className="px-10 pb-10">{children}</main>
        </div>
    );
}
