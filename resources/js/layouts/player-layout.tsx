import { Link, router, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
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
import { device, home, logout, ranking, schedule, standings } from '@/routes';
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
        ...(phase.value !== 'registration'
            ? [{ label: 'Hodnocení hráčů', href: ranking() }]
            : []),
        { label: 'Přihlásit jiné zařízení', href: device() },
        ...(auth.isAdmin ? [{ label: 'Administrace', href: dashboard() }] : []),
    ];

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="sticky top-0 z-10 border-b bg-background/95 backdrop-blur">
                <div className="mx-auto flex h-14 max-w-2xl items-center gap-3 px-4">
                    <Link href={home()} className="truncate font-semibold">
                        {name}
                    </Link>
                    <Badge variant="secondary" className="ml-auto">
                        {phase.label}
                    </Badge>
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Menu"
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
