import { Head, usePage } from "@inertiajs/react";
import type { ReactNode } from "react";

export function Shell({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    const { props } = usePage<{
        flash?: { success?: string; error?: string };
    }>();
    return (
        <div className="flex h-full flex-1 flex-col overflow-x-auto rounded-xl p-4 md:p-5 lg:p-8">
            <Head title={title} />
            <main>
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
