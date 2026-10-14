import { useForm } from '@inertiajs/react';
import { useRef } from 'react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import { useClipboard } from '@/hooks/use-clipboard';
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
    const jsonForm = useForm({ json: '' });
    const [, copy] = useClipboard();
    const jsonExample = JSON.stringify(
        [
            {
                company: 'Sinclair',
                building: 'High Street',
                unit: 'Unit 1',
                tenant: 'Example Ltd',
                move_in_date: '2026-01-01',
                move_out_date: null,
            },
        ],
        null,
        2,
    );
    function submitJson(event: FormEvent) {
        event.preventDefault();
        jsonForm.post('/imports/json', {
            preserveScroll: true,
            onSuccess: () => jsonForm.reset(),
        });
    }
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
            <div className="grid items-start gap-6 lg:grid-cols-2">
                <form
                    onSubmit={submit}
                    className="grid min-w-0 gap-5 rounded-xl border bg-card p-6"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="source">Source</Label>
                        <Select
                            value={form.data.source}
                            onValueChange={(value) =>
                                form.setData('source', value)
                            }
                            disabled={form.processing}
                        >
                            <SelectTrigger id="source" className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="re-leased">
                                    Re-Leased
                                </SelectItem>
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
                            Excel (.xlsx), up to 20 MB. The first sheet must
                            start with the exact Re-Leased export headers. Text
                            dates can use DD/MM/YYYY or YYYY-MM-DD; blank expiry
                            dates are saved as null. Rows without a start date
                            import the building, unit and tenant but skip the
                            tenancy.
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
                <form
                    onSubmit={submitJson}
                    className="grid min-w-0 gap-5 rounded-xl border bg-card p-6"
                >
                    <div>
                        <h2 className="text-lg font-semibold">Import JSON</h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Paste an array of rows. Each row supplies its
                            company; companies, buildings, units and tenants are
                            created or reused. Existing tenancies with matching
                            dates are skipped.
                        </p>
                    </div>
                    <div className="grid gap-2">
                        <div className="flex items-center justify-between gap-3">
                            <Label htmlFor="json-input">JSON data</Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={async () => {
                                    if (await copy(jsonExample)) {
                                        toast.success('JSON template copied.');
                                    } else {
                                        toast.error(
                                            'Could not copy the JSON template.',
                                        );
                                    }
                                }}
                            >
                                Copy Template
                            </Button>
                        </div>
                        <textarea
                            id="json-input"
                            rows={14}
                            value={jsonForm.data.json}
                            onChange={(event) =>
                                jsonForm.setData('json', event.target.value)
                            }
                            placeholder={jsonExample}
                            required
                            maxLength={2097152}
                            disabled={jsonForm.processing}
                            spellCheck={false}
                            aria-invalid={!!jsonForm.errors.json}
                            aria-describedby="json-help"
                            className="w-full rounded-md border border-input bg-transparent px-3 py-2 font-mono text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:opacity-50"
                        />
                        <p
                            id="json-help"
                            className="text-sm text-muted-foreground"
                        >
                            Use YYYY-MM-DD dates and null for ongoing tenancies.
                            All rows are validated before importing. Maximum 2
                            MB.
                        </p>
                        <InputError message={jsonForm.errors.json} />
                    </div>
                    <Button
                        className="w-fit"
                        disabled={
                            jsonForm.processing || !jsonForm.data.json.trim()
                        }
                    >
                        {jsonForm.processing ? 'Importing…' : 'Import JSON'}
                    </Button>
                </form>
            </div>
        </Shell>
    );
}

Index.layout = { breadcrumbs: [{ title: 'Import', href: '/imports' }] };
