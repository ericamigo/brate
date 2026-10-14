import { useForm } from '@inertiajs/react';
import { useRef } from 'react';
import type { FormEvent } from 'react';
import { Shell } from '@/components/page-shell';
import InputError from '@/components/input-error';
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

export default function Index({
    companies,
}: {
    companies: { id: number; name: string }[];
}) {
    const input = useRef<HTMLInputElement>(null);
    const form = useForm<{
        source: string;
        company_id: string;
        file: File | null;
    }>({ source: 're-leased', company_id: '', file: null });
    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/imports', {
            forceFormData: true,
            onSuccess: () => {
                form.reset('file');
                if (input.current) input.current.value = '';
            },
        });
    }
    return (
        <Shell title="Import">
            <h1 className="text-2xl font-semibold tracking-tight">Import</h1>
            <p className="mt-2 mb-6 text-sm text-muted-foreground">
                Import buildings, units, tenants and tenancies from a Re-Leased
                Excel export. Existing records with matching names and tenancy
                dates are reused.
            </p>
            <form
                onSubmit={submit}
                className="grid max-w-2xl gap-5 rounded-xl border bg-card p-6"
            >
                <div className="grid gap-2">
                    <Label htmlFor="source">Source</Label>
                    <Select
                        value={form.data.source}
                        onValueChange={(value) => form.setData('source', value)}
                        disabled={form.processing}
                    >
                        <SelectTrigger id="source" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="re-leased">Re-Leased</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.source} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="company">Company</Label>
                    <Select
                        value={form.data.company_id}
                        onValueChange={(value) =>
                            form.setData('company_id', value)
                        }
                        required
                        disabled={form.processing}
                    >
                        <SelectTrigger
                            id="company"
                            className="w-full"
                            aria-invalid={!!form.errors.company_id}
                        >
                            <SelectValue placeholder="Choose company" />
                        </SelectTrigger>
                        <SelectContent>
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
                    <InputError message={form.errors.company_id} />
                    {!companies.length && (
                        <p className="text-sm text-muted-foreground">
                            Create a company before importing.
                        </p>
                    )}
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="file">File</Label>
                    <Input
                        ref={input}
                        id="file"
                        type="file"
                        accept=".xlsx"
                        required
                        disabled={form.processing}
                        onChange={(event) =>
                            form.setData(
                                'file',
                                event.target.files?.[0] ?? null,
                            )
                        }
                        aria-invalid={!!form.errors.file}
                    />
                    <p className="text-sm text-muted-foreground">
                        Excel (.xlsx), up to 20 MB. The first sheet must start
                        with the exact Re-Leased export headers. Text dates can
                        use DD/MM/YYYY or YYYY-MM-DD; blank expiry dates are saved
                        as null. Rows without a start date import the building,
                        unit and tenant but skip the tenancy.
                    </p>
                    <InputError message={form.errors.file} />
                </div>
                {form.progress && (
                    <progress
                        max={100}
                        value={form.progress.percentage}
                        aria-label="Upload progress"
                        className="w-full"
                    />
                )}
                <Button
                    className="w-fit"
                    disabled={
                        form.processing ||
                        !form.data.file ||
                        !form.data.company_id
                    }
                >
                    {form.processing ? 'Importing…' : 'Import'}
                </Button>
            </form>
        </Shell>
    );
}

Index.layout = { breadcrumbs: [{ title: 'Import', href: '/imports' }] };
