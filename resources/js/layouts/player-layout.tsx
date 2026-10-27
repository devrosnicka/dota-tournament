import { Link, router, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import type { ReactNode } from 'react';
import Brand, { PhaseBadge } from '@/components/brand';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { isAtLeast } from '@/lib/phase';
import {
    device,
    final,
    home,
    logout,
    ranking,
    results,
    rules,
    schedule,
    standings,
} from '@/routes';
import { dashboard } from '@/routes/admin';

export default function PlayerLayout({ children }: { children: ReactNode }) {
    const { name, phase, auth } = usePage().props;

    useFlashToast();

    const links = [
        { label: 'Domů', href: home() },
        ...(isAtLeast(phase.value, 'group_stage')
            ? [
                  { label: 'Rozpis', href: schedule() },
                  { label: 'Tabulka', href: standings() },
              ]
            : []),
        ...(isAtLeast(phase.value, 'final_draft')
            ? [{ label: 'Finále', href: final() }]
            : []),
        ...(isAtLeast(phase.value, 'final')
            ? [{ label: 'Výsledky', href: results() }]
            : []),
        { label: 'Pravidla a FAQ', href: rules() },
        ...(phase.value !== 'registration'
            ? [{ label: 'Hodnocení hráčů', href: ranking() }]
            : []),
        { label: 'Přihlásit jiné zařízení', href: device() },
        ...(auth.isAdmin ? [{ label: 'Administrace', href: dashboard() }] : []),
    ];

    return (
        <div className="flex min-h-svh flex-col">
            <header className="hud-line sticky top-0 z-10 bg-background/80 backdrop-blur">
                <div className="mx-auto flex max-w-2xl flex-wrap items-center gap-x-3 gap-y-1 px-4 py-2 sm:h-14 sm:flex-nowrap sm:py-0">
                    <Link href={home()} className="min-w-0">
                        <Brand name={name} />
                    </Link>
                    {/* On phones the phase gets its own row, it does not fit next to the name. */}
                    <div className="order-last basis-full sm:order-none sm:ml-auto sm:basis-auto">
                        <PhaseBadge label={phase.label} />
                    </div>
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Menu"
                                className="ml-auto sm:ml-0"
                            >
                                <Menu />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="right" className="w-72">
                            <SheetHeader>
                                <SheetTitle>{auth.player?.nick}</SheetTitle>
                            </SheetHeader>
                            <nav className="grid gap-1 px-4">
                                {links.map((link) => (
                                    <SheetClose asChild key={link.label}>
                                        <Link
                                            href={link.href}
                                            className="rounded-md px-3 py-2 hover:bg-accent"
                                        >
                                            {link.label}
                                        </Link>
                                    </SheetClose>
                                ))}
                                <SheetClose asChild>
                                    <button
                                        type="button"
                                        className="rounded-md px-3 py-2 text-left text-muted-foreground hover:bg-accent"
                                        onClick={() =>
                                            router.post(logout.url())
                                        }
                                    >
                                        Odhlásit
                                    </button>
                                </SheetClose>
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>
            </header>
            <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-6">
                {children}
            </main>
        </div>
    );
}
