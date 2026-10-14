import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pages } from '@/components/pagination';
import type { Pagination } from '@/components/pagination';
import { Shell } from '@/components/page-shell';
import { FormDialog } from './form-dialog';
import { date } from '@/lib/date';

type RecordRow = { id: number; [key: string]: unknown };
const title = 'Billers';
const singular = 'Biller';
const resource = 'billers';

type Props = {
    columns: Record<string, string>;
    records: Pagination & { data: RecordRow[] };
};
function cell(record: RecordRow, key: string): string {
    let value: unknown = record;
    for (const part of key.split('.')) {
        value =
            typeof value === 'object' && value !== null
                ? (value as Record<string, unknown>)[part]
                : null;
    }
    return key.endsWith('_date') ||
        key === 'period_start' ||
        key === 'period_end'
        ? date(value as string | null)
        : typeof value === 'string' || typeof value === 'number'
          ? String(value)
          : '—';
}
export default function Index({ columns, records }: Props) {
    const { url, props } = usePage<{ errors: Record<string, string> }>();
    const [search, setSearch] = useState(
        new URLSearchParams(url.split('?')[1] ?? '').get('search') ?? '',
    );
    function submit(event: FormEvent) {
        event.preventDefault();
        router.get(`/${resource}`, { search });
    }
    return (
        <Shell title={title}>
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {records.total} records
                    </p>
                </div>
                <FormDialog />
            </div>
            <form
                onSubmit={submit}
                className="mb-6 flex flex-wrap items-end gap-3"
            >
                <div className="grid gap-2">
                    <Label htmlFor="search">Search by name</Label>
                    <Input
                        id="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        maxLength={255}
                    />
                </div>
                <Button variant="outline">Search</Button>
                <Button variant="ghost" asChild>
                    <Link href={`/${resource}`}>Clear</Link>
                </Button>
            </form>
            {props.errors?.search && (
                <p role="alert" className="mb-4 text-destructive">
                    {props.errors.search}
                </p>
            )}
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-left text-sm">
                    {records.data.length > 0 && (
                        <thead className="text-xs whitespace-nowrap">
                            <tr className="whitespace-nowrap">
                                {Object.entries(columns).map(([key, label]) => (
                                    <th key={key} className="px-5 py-4">
                                        {label}
                                    </th>
                                ))}
                                <th className="px-5 py-4 text-right" />
                            </tr>
                        </thead>
                    )}
                    <tbody>
                        {records.data.map((record) => (
                            <tr
                                key={record.id}
                                className="border-t whitespace-nowrap"
                            >
                                {Object.keys(columns).map((key) => (
                                    <td key={key} className="px-5 py-4">
                                        {cell(record, key)}
                                    </td>
                                ))}
                                <td className="px-5 py-4">
                                    <div className="-my-2 flex justify-end gap-2">
                                        <FormDialog record={record} />
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive"
                                            onClick={() => {
                                                if (
                                                    window.confirm(
                                                        `Delete this ${singular.toLowerCase()}? This cannot be undone.`,
                                                    )
                                                )
                                                    router.delete(
                                                        `/${resource}/${record.id}`,
                                                    );
                                            }}
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {!records.data.length && (
                            <tr className="whitespace-nowrap">
                                <td
                                    colSpan={Object.keys(columns).length + 1}
                                    className="p-12 text-center text-muted-foreground"
                                >
                                    No records found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            <Pages pagination={records} />
        </Shell>
    );
}
