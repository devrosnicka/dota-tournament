import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Brand, { PhaseBadge } from '@/components/brand';
import { Card, CardContent } from '@/components/ui/card';
import { useFlashToast } from '@/hooks/use-flash-toast';

export default function GuestLayout({ children }: { children: ReactNode }) {
    const { name, phase } = usePage().props;

    useFlashToast();

    return (
        <div className="flex min-h-svh flex-col items-center justify-center px-4 py-10">
            <div className="w-full max-w-sm">
                <div className="mb-6 flex flex-col items-center gap-3 text-center">
                    <h1>
                        <Brand name={name} className="text-2xl" />
                    </h1>
                    <PhaseBadge label={phase.label} />
                </div>
                <Card>
                    <CardContent className="grid gap-6">{children}</CardContent>
                </Card>
            </div>
        </div>
    );
}
