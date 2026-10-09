import { Link, router, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { PhaseBadge } from '@/components/brand';
import { Button } from '@/components/ui/button';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import {
    dashboard,
    final,
    logout,
    results,
    schedule,
    seeding,
    tiebreaks,
} from '@/routes/admin';
import { index as players } from '@/routes/admin/players';

export default function AdminLayout({ children }: { children: ReactNode }) {
    const { phase, auth } = usePage().props;
    const currentUrl = usePage().url;

    useFlashToast();

    const links = [
        { label: 'Přehled', href: dashboard() },
        { label: 'Hráči', href: players() },
        { label: 'Nasazení', href: seeding() },
        { label: 'Rozpis', href: schedule() },
        { label: 'Výsledky', href: results() },
        { label: 'Shody', href: tiebreaks() },
        { label: 'Finále', href: final() },
    ];

    return (
        <div className="flex min-h-svh flex-col">
            <header className="hud-line bg-background/80 backdrop-blur">
                <div className="mx-auto flex h-14 max-w-4xl items-center gap-3 px-4">
                    <Link
                        href={dashboard()}
                        className="font-display font-bold tracking-[0.12em] uppercase"
                    >
                        Administrace
                    </Link>
                    <PhaseBadge label={phase.label} />
                    <div className="ml-auto flex items-center gap-1">
                        {auth.player && (
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={home()}>Pohled hráče</Link>
                            </Button>
                        )}
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => router.post(logout.url())}
                        >
                            Odhlásit
                        </Button>
                    </div>
                </div>
                <nav className="mx-auto flex max-w-4xl gap-1 overflow-x-auto px-4 pb-2">
                    {links.map((link) => (
                        <Link
                            key={link.label}
                            href={link.href}
                            className={cn(
                                'rounded-md px-3 py-1.5 text-sm whitespace-nowrap hover:bg-accent',
                                currentUrl.split('?')[0] === link.href.url &&
                                    'bg-primary/25 font-medium text-foreground',
                            )}
                        >
                            {link.label}
                        </Link>
                    ))}
                </nav>
            </header>
            <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-6">
                {children}
            </main>
        </div>
    );
}
