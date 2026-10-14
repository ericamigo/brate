import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export type Pagination = {
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

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
