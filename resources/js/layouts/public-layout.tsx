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
                <div className="mx-auto flex h-14 max-w-2xl items-center gap-3 px-4">
                    <Brand name={name} />
                    <PhaseBadge label={phase.label} className="ml-auto" />
                </div>
            </header>
            <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-6">
                {children}
            </main>
        </div>
    );
}
