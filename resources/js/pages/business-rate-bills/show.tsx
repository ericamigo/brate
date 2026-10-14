import { Link, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import type { FormEvent } from 'react';
import { Shell } from '@/components/page-shell';
import InputError from '@/components/input-error';
import { DeleteDocumentButton } from '@/components/delete-document-button';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { date } from '@/lib/date';
import { formatMoney } from '@/lib/utils';

type Bill = {
    id: number;
    case_number: string | null;
    reference_number: string | null;
    stage: string | null;
    council: string | null;
    amount: string;
    period_start: string;
    period_end: string;
    biller: { name: string };
    unit: {
        name: string;
        building: { name: string; company: { name: string } };
    };
    documents: { id: number; filename: string; created_at: string }[];
};

export default function Show({ bill }: { bill: Bill }) {
    const input = useRef<HTMLInputElement>(null);
    const form = useForm<{ files: File[] }>({ files: [] });
    function upload(event: FormEvent) {
        event.preventDefault();
        form.post(`/business-rate-bills/${bill.id}/documents`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                if (input.current) input.current.value = '';
            },
        });
    }
    return (
        <Shell title={`Bill ${bill.case_number ?? '#' + bill.id}`}>
            <div className="mb-6 flex items-center justify-between gap-4">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Bill {bill.case_number ?? '#' + bill.id}
                </h1>
                <div className="flex gap-2">
                    <Button variant="outline" asChild>
                        <Link href="/business-rate-bills">Back to bills</Link>
                    </Button>
                    <Button asChild>
                        <Link href={`/business-rate-bills/${bill.id}/edit`}>
                            Edit bill
                        </Link>
                    </Button>
                </div>
            </div>
            <dl className="mb-6 grid gap-4 rounded-xl border bg-card p-6 sm:grid-cols-2 lg:grid-cols-3">
                {Object.entries({
                    'Case number': bill.case_number,
                    Reference: bill.reference_number,
                    Company: bill.unit.building.company.name,
                    Building: bill.unit.building.name,
                    Unit: bill.unit.name,
                    Biller: bill.biller.name,
                    Council: bill.council,
                    'Period covered': `${date(bill.period_start)} – ${date(bill.period_end)}`,
                    Amount: formatMoney(bill.amount),
                    Stage: bill.stage
                        ? bill.stage[0].toUpperCase() + bill.stage.slice(1)
                        : null,
                }).map(([label, value]) => (
                    <div key={label}>
                        <dt className="text-sm text-muted-foreground">
                            {label}
                        </dt>
                        <dd className="mt-1 font-medium">{value ?? '—'}</dd>
                    </div>
                ))}
            </dl>
            <h2 className="mb-4 text-lg font-semibold">Documents</h2>
            <form
                onSubmit={upload}
                className="mb-6 grid max-w-2xl gap-3 rounded-xl border p-5"
            >
                <Label htmlFor="bill-files">Upload images or PDFs</Label>
                <Input
                    ref={input}
                    id="bill-files"
                    type="file"
                    multiple
                    required
                    accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,application/pdf"
                    disabled={form.processing}
                    onChange={(event) =>
                        form.setData(
                            'files',
                            Array.from(event.target.files ?? []),
                        )
                    }
                />
                <p className="text-sm text-muted-foreground">
                    Up to 10 files, 20 MB each.
                </p>
                {Object.entries(form.errors).map(([key, message]) => (
                    <InputError key={key} message={message} />
                ))}
                {form.progress && (
                    <progress
                        value={form.progress.percentage}
                        max={100}
                        aria-label="Upload progress"
                        className="w-full"
                    />
                )}
                <Button
                    className="w-fit"
                    disabled={form.processing || !form.data.files.length}
                >
                    {form.processing ? 'Uploading…' : 'Upload documents'}
                </Button>
            </form>
            <div className="overflow-x-auto rounded-xl border">
                <table className="w-full text-left text-sm">
                    {bill.documents.length > 0 && (
                        <thead className="text-xs whitespace-nowrap">
                            <tr className="whitespace-nowrap">
                                <th className="px-5 py-4">Filename</th>
                                <th className="px-5 py-4">Uploaded</th>
                                <th className="px-5 py-4 text-right" />
                            </tr>
                        </thead>
                    )}
                    <tbody>
                        {bill.documents.map((document) => (
                            <tr
                                key={document.id}
                                className="border-t whitespace-nowrap"
                            >
                                <td className="px-5 py-4">
                                    {document.filename}
                                </td>
                                <td className="px-5 py-4">
                                    {date(document.created_at)}
                                </td>
                                <td className="px-5 py-4">
                                    <div className="-my-2 flex justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <a
                                                href={`/documents/${document.id}`}
                                            >
                                                Download
                                            </a>
                                        </Button>
                                        <DeleteDocumentButton
                                            document={document}
                                        />
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {!bill.documents.length && (
                            <tr className="whitespace-nowrap">
                                <td
                                    colSpan={3}
                                    className="p-8 text-center text-muted-foreground"
                                >
                                    No documents uploaded yet.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </Shell>
    );
}

Show.layout = {
    breadcrumbs: [
        { title: 'Business Rate Bills', href: '/business-rate-bills' },
        { title: 'Details', href: '#' },
    ],
};
