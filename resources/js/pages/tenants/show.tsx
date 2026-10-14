import { Link } from '@inertiajs/react';
import { Shell } from '@/components/page-shell';
import { Pages } from '@/components/pagination';
import type { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { date } from '@/lib/date';
import { FormDialog } from './form-dialog';

type Tenancy = {
    id: number;
    move_in_date: string;
    move_out_date: string | null;
    unit: {
        name: string;
        building: { name: string; company: { name: string } };
    };
};
type Props = {
    tenant: { id: number; name: string };
    tenancies: Pagination & { data: Tenancy[] };
};

export default function Show({ tenant, tenancies }: Props) {
    return (
        <Shell title={tenant.name}>
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {tenant.name}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {tenancies.total} tenancies
                    </p>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" asChild>
                        <Link href="/tenants">Back to tenants</Link>
                    </Button>
                    <FormDialog record={tenant} />
                </div>
            </div>
            <h2 className="mb-4 text-lg font-semibold">Tenancies</h2>
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-left text-sm">
                    {tenancies.data.length > 0 && (
                        <thead className="text-xs whitespace-nowrap">
                            <tr className="whitespace-nowrap">
                                {[
                                    'Company',
                                    'Building',
                                    'Unit',
                                    'Move-In Date',
                                    'Move-Out Date',
                                ].map((label) => (
                                    <th key={label} className="px-5 py-4">
                                        {label}
                                    </th>
                                ))}
                                <th className="px-5 py-4 text-right" />
                            </tr>
                        </thead>
                    )}
                    <tbody>
                        {tenancies.data.map((tenancy) => (
                            <tr
                                key={tenancy.id}
                                className="border-t whitespace-nowrap"
                            >
                                <td className="px-5 py-4">
                                    {tenancy.unit.building.company.name}
                                </td>
                                <td className="px-5 py-4">
                                    {tenancy.unit.building.name}
                                </td>
                                <td className="px-5 py-4">
                                    {tenancy.unit.name}
                                </td>
                                <td className="px-5 py-4">
                                    {date(tenancy.move_in_date)}
                                </td>
                                <td className="px-5 py-4">
                                    {date(tenancy.move_out_date)}
                                </td>
                                <td className="px-5 py-4">
                                    <div className="-my-2 flex justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`/tenancies/${tenancy.id}/edit`}
                                            >
                                                Edit Tenancy
                                            </Link>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {!tenancies.data.length && (
                            <tr className="whitespace-nowrap">
                                <td
                                    colSpan={6}
                                    className="p-12 text-center text-muted-foreground"
                                >
                                    No tenancies linked to this tenant.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            <Pages pagination={tenancies} />
        </Shell>
    );
}
Show.layout = {
    breadcrumbs: [
        { title: 'Tenants', href: '/tenants' },
        { title: 'Details', href: '#' },
    ],
};
