export function date(value?: string | null): string {
    if (!value) return '—';
    const day = value.slice(0, 10);
    return new Intl.DateTimeFormat('en', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${day}T00:00:00Z`));
}
