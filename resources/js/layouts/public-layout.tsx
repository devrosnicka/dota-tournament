import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Brand, { PhaseBadge } from '@/components/brand';
import { useFlashToast } from '@/hooks/use-flash-toast';

/**
 * Wide page for guests, e.g. the tournament flow before registering.
 */
export default function PublicLayout({ children }: { children: ReactNode }) {
    const { name, phase } = usePage().props;

    useFlashToast();

    return (
        <div className="flex min-h-svh flex-col">
            <header className="hud-line bg-background/80 backdrop-blur">
                <div className="mx-auto flex max-w-2xl flex-col gap-1.5 px-4 py-3 sm:h-14 sm:flex-row sm:items-center sm:gap-3 sm:py-0">
                    <Brand name={name} />
                    <PhaseBadge label={phase.label} className="sm:ml-auto" />
                </div>
            </header>
            <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-6">
                {children}
            </main>
        </div>
    );
}
