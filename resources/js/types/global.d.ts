import type { Auth, Phase } from '@/types';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            phase: { value: Phase; label: string };
            auth: Auth;
            [key: string]: unknown;
        };
    }
}
