import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { useFlashToast } from '@/hooks/use-flash-toast';

/**
 * Wide page for guests, e.g. the tournament flow before registering.
 */
export default function PublicLayout({ children }: { children: ReactNode }) {
    const { name, phase } = usePage().props;

    useFlashToast();

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="border-b">
                <div className="mx-auto flex h-14 max-w-2xl items-center gap-3 px-4">
                    <span className="truncate font-semibold">{name}</span>
                    <Badge variant="secondary" className="ml-auto">
                        {phase.label}
                    </Badge>
                </div>
            </header>
            <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-6">
                {children}
            </main>
        </div>
    );
}
