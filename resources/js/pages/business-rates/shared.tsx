import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';

export type Pagination = {
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

const navigation = [
    ['/', 'Occupancy Report'],
    ['/companies', 'Companies'],
    ['/buildings', 'Buildings'],
    ['/units', 'Units'],
    ['/tenants', 'Tenants'],
    ['/tenancies', 'Tenancies'],
    ['/billers', 'Billers'],
    ['/business-rate-bills', 'Bills'],
    ['/documents', 'Documents'],
];

export const controlClass =
    'w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';

export function Shell({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    const { url, props } = usePage<{
        flash?: { success?: string; error?: string };
    }>();
    return (
        <div className="min-h-screen bg-background text-foreground">
            <Head title={title} />
            <header className="border-b bg-card">
                <div className="mx-auto max-w-screen-2xl px-6 py-5 text-xl font-semibold">
                    <Link href="/">Business Rates</Link>
                </div>
                <nav
                    aria-label="Business rates navigation"
                    className="mx-auto flex max-w-screen-2xl flex-wrap gap-1 px-6 pb-4"
                >
                    {navigation.map(([href, label]) => (
                        <Button
                            key={href}
                            variant={
                                url.split('?')[0] === href ||
                                (href !== '/' && url.startsWith(`${href}/`))
                                    ? 'secondary'
                                    : 'ghost'
                            }
                            asChild
                        >
                            <Link href={href}>{label}</Link>
                        </Button>
                    ))}
                </nav>
            </header>
            <main className="mx-auto max-w-screen-2xl px-6 py-8">
                {props.flash?.success && (
                    <div
                        role="status"
                        className="mb-5 rounded-lg border bg-card p-4"
                    >
                        {props.flash.success}
                    </div>
                )}
                {props.flash?.error && (
                    <div
                        role="alert"
                        className="mb-5 rounded-lg border border-destructive p-4 text-destructive"
                    >
                        {props.flash.error}
                    </div>
                )}
                {children}
            </main>
        </div>
    );
}

export function Pages({ pagination }: { pagination: Pagination }) {
    return (
        <nav
            aria-label="Pagination"
            className="mt-6 flex flex-wrap items-center gap-2"
        >
            {pagination.links.map((link, index) => {
                const label = link.label
                    .replace('&laquo;', '«')
                    .replace('&raquo;', '»')
                    .replace(/<[^>]*>/g, '');
                return link.url ? (
                    <Button
                        key={index}
                        variant={link.active ? 'secondary' : 'outline'}
                        size="sm"
                        asChild
                    >
                        <Link
                            href={link.url}
                            aria-current={link.active ? 'page' : undefined}
                        >
                            {label}
                        </Link>
                    </Button>
                ) : (
                    <Button key={index} variant="outline" size="sm" disabled>
                        {label}
                    </Button>
                );
            })}
        </nav>
    );
}

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
