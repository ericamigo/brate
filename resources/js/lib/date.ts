export function date(value?: string | null): string {
    if (!value) return '—';
    const day = value.slice(0, 10);
    return new Intl.DateTimeFormat('en-GB', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${day}T00:00:00Z`));
}
