import type { Phase } from '@/types';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            phase: { value: Phase; label: string };
            [key: string]: unknown;
        };
    }
}
