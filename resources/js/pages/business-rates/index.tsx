import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pages, Shell, date } from './shared';
import type { Pagination } from './shared';

type RecordRow = { id: number; [key: string]: unknown };
type Props = {
    title: string;
    singular: string;
    resource: string;
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
export default function Index({
    title,
    singular,
    resource,
    columns,
    records,
}: Props) {
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
                    <h1 className="text-2xl font-semibold">{title}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {records.total} records
                    </p>
                </div>
                <Button asChild>
                    <Link href={`/${resource}/create`}>Add {singular}</Link>
                </Button>
            </div>
            {resource === 'tenancies' && (
                <p className="mb-6 rounded-lg border bg-muted p-4 text-sm">
                    When occupants change, close the previous tenancy and create
                    a new record. Move-out is the last day of occupancy; leave
                    it blank for ongoing occupancy.
                </p>
            )}
            <form
                onSubmit={submit}
                className="mb-6 flex flex-wrap items-end gap-3"
            >
                <div className="grid gap-2">
                    <Label htmlFor="search">
                        {resource === 'tenancies' ||
                        resource === 'business-rate-bills'
                            ? 'Search by unit name'
                            : 'Search by name'}
                    </Label>
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
                    <thead className="bg-muted">
                        <tr>
                            {Object.entries(columns).map(([key, label]) => (
                                <th key={key} className="px-5 py-4">
                                    {label}
                                </th>
                            ))}
                            <th className="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {records.data.map((record) => (
                            <tr key={record.id} className="border-t">
                                {Object.keys(columns).map((key) => (
                                    <td key={key} className="px-5 py-4">
                                        {cell(record, key)}
                                    </td>
                                ))}
                                <td className="px-5 py-4">
                                    <div className="flex justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`/${resource}/${record.id}/edit`}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
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
                            <tr>
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
