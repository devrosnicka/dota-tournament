export function formatTime(iso: string): string {
    return new Date(iso).toLocaleTimeString('cs-CZ', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function formatDateTime(iso: string): string {
    return new Date(iso).toLocaleString('cs-CZ', {
        day: 'numeric',
        month: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function formatPoints(points: number): string {
    return points.toLocaleString('cs-CZ', { maximumFractionDigits: 1 });
}

export function formatDiff(diff: number): string {
    return diff > 0 ? `+${diff}` : String(diff);
}

/** "kolo", "kola" or "kol" to go after the number of rounds. */
export function roundsWord(rounds: number): string {
    if (rounds === 1) {
        return 'kolo';
    }

    return rounds < 5 ? 'kola' : 'kol';
}
