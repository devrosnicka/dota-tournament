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
