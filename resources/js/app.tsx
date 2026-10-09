import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import AdminLayout from '@/layouts/admin-layout';
import GuestLayout from '@/layouts/guest-layout';
import PlayerLayout from '@/layouts/player-layout';
import PublicLayout from '@/layouts/public-layout';
import TvLayout from '@/layouts/tv-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Dota LAN turnaj';

void createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/') || name === 'admin/login':
                return GuestLayout;
            case name === 'how-it-works':
                return PublicLayout;
            case name.startsWith('admin/'):
                return AdminLayout;
            case name.startsWith('tv/'):
                return TvLayout;
            default:
                return PlayerLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});
