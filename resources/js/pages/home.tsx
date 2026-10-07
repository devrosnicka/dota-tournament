import { Head, usePage } from '@inertiajs/react';

export default function Home() {
    const { name, phase } = usePage().props;

    return (
        <>
            <Head title="Úvod" />
            <h1 className="text-2xl font-bold">{name}</h1>
            <p className="mt-2 text-muted-foreground">
                Aktuální fáze: {phase.label}
            </p>
        </>
    );
}
