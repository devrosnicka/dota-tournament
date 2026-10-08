import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useFlashToast } from '@/hooks/use-flash-toast';

export default function GuestLayout({ children }: { children: ReactNode }) {
    const { name, phase } = usePage().props;

    useFlashToast();

    return (
        <div className="flex min-h-svh flex-col items-center justify-center bg-muted/40 px-4 py-10">
            <div className="w-full max-w-sm">
                <div className="mb-6 flex flex-col items-center gap-2 text-center">
                    <h1 className="text-2xl font-bold">{name}</h1>
                    <Badge variant="secondary">{phase.label}</Badge>
                </div>
                <Card>
                    <CardContent className="grid gap-6">{children}</CardContent>
                </Card>
            </div>
        </div>
    );
}
