import { Link, router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pages, Shell, controlClass, date } from './shared';
import type { Pagination } from './shared';

type Property = {
    id: number;
    name: string;
    company?: { name: string };
    building?: { name: string };
};
type Row = {
    bill_id: number;
    reference_number: string | null;
    company: string;
    building: string;
    unit: string;
    period_start: string;
    period_end: string;
    occupant: string | null;
    tenancy_id: number | null;
    move_in_date: string | null;
    move_out_date: string | null;
    occupied_start: string | null;
    occupied_end: string | null;
};
type Filters = {
    company_id: string;
    building_id: string;
    unit_id: string;
    period_start: string;
    period_end: string;
};
type Props = {
    bills: Pagination;
    rows: Row[];
    filters: Partial<Filters>;
    companies: Property[];
    buildings: Property[];
    units: Property[];
};
export default function Report({
    bills,
    rows,
    filters,
    companies,
    buildings,
    units,
}: Props) {
    const form = useForm<Filters>({
        company_id: String(filters.company_id ?? ''),
        building_id: String(filters.building_id ?? ''),
        unit_id: String(filters.unit_id ?? ''),
        period_start: filters.period_start ?? '',
        period_end: filters.period_end ?? '',
    });
    const { errors } = usePage<{ errors: Record<string, string> }>().props;
    function submit(event: FormEvent) {
        event.preventDefault();
        router.get('/', form.data);
    }
    function change(
        key: 'company_id' | 'building_id' | 'unit_id',
        value: string,
    ) {
        const next = { ...form.data, [key]: value };
        if (key === 'company_id') {
            next.building_id = '';
            next.unit_id = '';
        }
        if (key === 'building_id') next.unit_id = '';
        form.setData(next);
        if (key !== 'unit_id') router.get('/', next, { preserveState: true });
    }
    const selects: {
        name: 'company_id' | 'building_id' | 'unit_id';
        label: string;
        options: Property[];
    }[] = [
        { name: 'company_id', label: 'Company', options: companies },
        { name: 'building_id', label: 'Building', options: buildings },
        { name: 'unit_id', label: 'Unit', options: units },
    ];
    return (
        <Shell title="Occupancy Report">
            <div className="mb-7 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-semibold">Occupancy Report</h1>
                    <p className="mt-2 text-muted-foreground">
                        Identify occupants during each business rates billing
                        period.
                    </p>
                </div>
                <Button asChild>
                    <Link href="/business-rate-bills/create">Add Bill</Link>
                </Button>
            </div>
            <form
                onSubmit={submit}
                className="mb-6 rounded-lg border bg-card p-5"
            >
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    {selects.map(({ name, label, options }) => (
                        <div key={name} className="grid gap-2">
                            <Label htmlFor={name}>{label}</Label>
                            <select
                                id={name}
                                value={form.data[name]}
                                onChange={(event) =>
                                    change(name, event.target.value)
                                }
                                className={controlClass}
                            >
                                <option value="">
                                    All {label.toLowerCase()}s
                                </option>
                                {options.map((option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.company?.name ??
                                            option.building?.name}
                                        {(option.company || option.building) &&
                                            ' / '}
                                        {option.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors[name]} />
                        </div>
                    ))}
                    {(['period_start', 'period_end'] as const).map((name) => (
                        <div key={name} className="grid gap-2">
                            <Label htmlFor={name}>
                                Billing period{' '}
                                {name === 'period_start' ? 'from' : 'to'}
                            </Label>
                            <Input
                                type="date"
                                id={name}
                                value={form.data[name]}
                                onChange={(event) =>
                                    form.setData(name, event.target.value)
                                }
                            />
                            <InputError message={errors[name]} />
                        </div>
                    ))}
                </div>
                <div className="mt-5 flex flex-wrap items-center gap-4">
                    <Button>Apply Filters</Button>
                    <Button variant="outline" asChild>
                        <Link href="/">Reset</Link>
                    </Button>
                    <p className="text-xs text-muted-foreground">
                        Shows bills overlapping the selected date range.
                    </p>
                </div>
            </form>
            <p className="mb-4 text-sm text-muted-foreground">
                {bills.total} matching bills · {rows.length} occupancy rows on
                this page · Inclusive dates
            </p>
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-left text-sm">
                    <thead className="bg-muted">
                        <tr>
                            {[
                                'Building',
                                'Unit',
                                'Period Covered',
                                'Occupant',
                                'Move-in Date',
                                'Move-out Date',
                                'Occupied Period',
                            ].map((label) => (
                                <th
                                    key={label}
                                    className="px-5 py-4 whitespace-nowrap"
                                >
                                    {label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr
                                key={`${row.bill_id}-${row.tenancy_id ?? 'vacant'}`}
                                className="border-t"
                            >
                                <td className="px-5 py-4">
                                    {row.building}
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {row.company}
                                    </p>
                                </td>
                                <td className="px-5 py-4">{row.unit}</td>
                                <td className="px-5 py-4 whitespace-nowrap">
                                    {date(row.period_start)} –{' '}
                                    {date(row.period_end)}
                                    <Link
                                        href={`/business-rate-bills/${row.bill_id}/edit`}
                                        className="mt-1 block text-xs underline"
                                    >
                                        {row.reference_number ??
                                            `Bill #${row.bill_id}`}
                                    </Link>
                                </td>
                                <td className="px-5 py-4">
                                    {row.occupant ?? 'Vacant'}
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap">
                                    {date(row.move_in_date)}
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap">
                                    {date(row.move_out_date)}
                                </td>
                                <td className="px-5 py-4 whitespace-nowrap">
                                    {row.occupied_start
                                        ? `${date(row.occupied_start)} – ${date(row.occupied_end)}`
                                        : '—'}
                                </td>
                            </tr>
                        ))}
                        {!rows.length && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="p-12 text-center text-muted-foreground"
                                >
                                    No bills found. Adjust the filters or add a
                                    bill.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            <Pages pagination={bills} />
            <p className="mt-5 text-xs text-muted-foreground">
                Paginated by bill to keep matching tenancies together. Occupied
                periods use the full billing period. This report identifies
                occupancy and does not assign rates liability.
            </p>
        </Shell>
    );
}
