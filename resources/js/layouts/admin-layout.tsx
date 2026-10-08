import { Link, router, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { dashboard, logout, seeding } from '@/routes/admin';
import { index as players } from '@/routes/admin/players';

export default function AdminLayout({ children }: { children: ReactNode }) {
    const { phase, auth } = usePage().props;
    const currentUrl = usePage().url;

    useFlashToast();

    const links = [
        { label: 'Přehled', href: dashboard() },
        { label: 'Hráči', href: players() },
        { label: 'Nasazení', href: seeding() },
    ];

    return (
        <div className="flex min-h-svh flex-col bg-muted/40">
            <header className="border-b bg-background">
                <div className="mx-auto flex h-14 max-w-4xl items-center gap-3 px-4">
                    <Link href={dashboard()} className="font-semibold">
                        Administrace
                    </Link>
                    <Badge variant="secondary">{phase.label}</Badge>
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
                                    'bg-accent font-medium',
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
