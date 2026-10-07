import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { home } from '@/routes';

export default function PlayerLayout({ children }: { children: ReactNode }) {
    const { name, phase } = usePage().props;

    useFlashToast();

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="sticky top-0 z-10 border-b bg-background/95 backdrop-blur">
                <div className="mx-auto flex h-14 max-w-2xl items-center justify-between gap-3 px-4">
                    <Link href={home()} className="truncate font-semibold">
                        {name}
                    </Link>
                    <Badge variant="secondary">{phase.label}</Badge>
                </div>
            </header>
            <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-6">
                {children}
            </main>
        </div>
    );
}
