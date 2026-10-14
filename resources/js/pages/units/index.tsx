import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/input-error';
import { Pages } from '@/components/pagination';
import type { Pagination } from '@/components/pagination';
import { Shell } from '@/components/page-shell';
import { date } from '@/lib/date';

type RecordRow = { id: number; [key: string]: unknown };
const title = 'Units';
const singular = 'Unit';
const resource = 'units';

type Props = {
    columns: Record<string, string>;
    records: Pagination & { data: RecordRow[] };
    filters: {
        search?: string;
        company_id?: number | string;
        building_id?: number | string;
    };
    companies: { id: number; name: string }[];
    buildings: {
        id: number;
        name: string;
        company_id: number;
        company: { name: string };
    }[];
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
    columns,
    records,
    filters,
    companies,
    buildings,
}: Props) {
    const { url, props } = usePage<{ errors: Record<string, string> }>();
    const [search, setSearch] = useState(
        new URLSearchParams(url.split('?')[1] ?? '').get('search') ?? '',
    );
    const [companyId, setCompanyId] = useState(
        String(filters.company_id ?? ''),
    );
    const [buildingId, setBuildingId] = useState(
        String(filters.building_id ?? ''),
    );
    const availableBuildings = buildings.filter(
        (building) => !companyId || String(building.company_id) === companyId,
    );
    function submit(event: FormEvent) {
        event.preventDefault();
        router.get(`/${resource}`, {
            search,
            company_id: companyId,
            building_id: buildingId,
        });
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
                <Button asChild>
                    <Link href={`/${resource}/create`}>Add {singular}</Link>
                </Button>
            </div>
            <form
                onSubmit={submit}
                className="mb-6 flex flex-wrap items-end gap-3"
            >
                <div className="grid min-w-48 gap-2">
                    <Label htmlFor="company-filter">Company</Label>
                    <Select
                        value={companyId || 'all'}
                        onValueChange={(value) => {
                            setCompanyId(value === 'all' ? '' : value);
                            setBuildingId('');
                        }}
                    >
                        <SelectTrigger id="company-filter" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All companies</SelectItem>
                            {companies.map((company) => (
                                <SelectItem
                                    key={company.id}
                                    value={String(company.id)}
                                >
                                    {company.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={props.errors.company_id} />
                </div>
                <div className="grid min-w-48 gap-2">
                    <Label htmlFor="building-filter">Building</Label>
                    <Select
                        value={buildingId || 'all'}
                        onValueChange={(value) =>
                            setBuildingId(value === 'all' ? '' : value)
                        }
                    >
                        <SelectTrigger id="building-filter" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All buildings</SelectItem>
                            {availableBuildings.map((building) => (
                                <SelectItem
                                    key={building.id}
                                    value={String(building.id)}
                                >
                                    {companyId
                                        ? building.name
                                        : `${building.company.name} / ${building.name}`}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={props.errors.building_id} />
                </div>
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
                    <thead className="bg-muted">
                        <tr className="whitespace-nowrap">
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
                            <tr
                                key={record.id}
                                className="border-t whitespace-nowrap"
                            >
                                {Object.keys(columns).map((key) => (
                                    <td key={key} className={`px-5 py-4${key === 'name' ? ' font-bold' : ''}`}>
                                        {cell(record, key)}
                                    </td>
                                ))}
                                <td className="px-5 py-4">
                                    <div className="-my-2 flex justify-end gap-2">
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
