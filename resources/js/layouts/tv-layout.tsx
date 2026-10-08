import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

/**
 * Projector view: always dark, large type, no navigation.
 */
export default function TvLayout({ children }: { children: ReactNode }) {
    const { name, phase } = usePage().props;

    return (
        <div className="dark min-h-svh bg-background text-foreground">
            <header className="flex items-baseline justify-between px-10 pt-8 pb-4">
                <h1 className="text-4xl font-bold">{name}</h1>
                <span className="text-3xl text-muted-foreground">
                    {phase.label}
                </span>
            </header>
            <main className="px-10 pb-10">{children}</main>
        </div>
    );
}
