import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';

export function Shell({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <div className="flex h-full flex-1 flex-col overflow-x-auto rounded-xl p-4 md:p-5 lg:p-8">
            <Head title={title} />
            <main>{children}</main>
        </div>
    );
}
