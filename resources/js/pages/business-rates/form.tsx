import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Shell, controlClass } from './shared';

type Field = {
    name: string;
    label: string;
    type: string;
    required: boolean;
    options?: Record<string, string>;
};
type Props = {
    title: string;
    singular: string;
    resource: string;
    record: { id?: number; [key: string]: unknown };
    fields: Field[];
};
export default function ManagementForm({
    title,
    singular,
    resource,
    record,
    fields,
}: Props) {
    const form = useForm<Record<string, string>>(
        Object.fromEntries(
            fields.map((field) => {
                let value =
                    record[field.name] == null
                        ? ''
                        : String(record[field.name]);
                if (field.type === 'date') value = value.slice(0, 10);
                return [field.name, value];
            }),
        ),
    );
    function submit(event: FormEvent) {
        event.preventDefault();
        if (record.id) form.put(`/${resource}/${record.id}`);
        else form.post(`/${resource}`);
    }
    return (
        <Shell title={`${record.id ? 'Edit' : 'Add'} ${singular}`}>
            <Link
                href={`/${resource}`}
                className="text-sm text-muted-foreground"
            >
                ← {title}
            </Link>
            <h1 className="my-6 text-2xl font-semibold">
                {record.id ? 'Edit' : 'Add'} {singular}
            </h1>
            <form
                onSubmit={submit}
                className="grid max-w-2xl gap-5 rounded-lg border bg-card p-6"
            >
                {fields.map((field) => (
                    <div key={field.name} className="grid gap-2">
                        <Label htmlFor={field.name}>
                            {field.label}
                            {field.required && ' *'}
                        </Label>
                        {field.type === 'select' ? (
                            <select
                                id={field.name}
                                value={form.data[field.name]}
                                onChange={(event) =>
                                    form.setData(field.name, event.target.value)
                                }
                                required={field.required}
                                className={controlClass}
                            >
                                <option value="">Choose {field.label}</option>
                                {Object.entries(field.options ?? {}).map(
                                    ([id, label]) => (
                                        <option key={id} value={id}>
                                            {label}
                                        </option>
                                    ),
                                )}
                            </select>
                        ) : field.type === 'textarea' ? (
                            <textarea
                                id={field.name}
                                value={form.data[field.name]}
                                onChange={(event) =>
                                    form.setData(field.name, event.target.value)
                                }
                                rows={3}
                                className={controlClass}
                            />
                        ) : (
                            <Input
                                id={field.name}
                                type={field.type}
                                value={form.data[field.name]}
                                onChange={(event) =>
                                    form.setData(field.name, event.target.value)
                                }
                                required={field.required}
                                step={
                                    field.type === 'number' ? '0.01' : undefined
                                }
                                min={field.type === 'number' ? 0 : undefined}
                            />
                        )}
                        <InputError message={form.errors[field.name]} />
                    </div>
                ))}
                {resource === 'tenancies' && (
                    <p className="text-sm text-muted-foreground">
                        Move-out is the last day of occupancy. For a new
                        occupant, create a separate tenancy to preserve history.
                    </p>
                )}
                <div className="mt-3 flex gap-3">
                    <Button disabled={form.processing}>Save {singular}</Button>
                    <Button variant="outline" asChild>
                        <Link href={`/${resource}`}>Cancel</Link>
                    </Button>
                </div>
            </form>
        </Shell>
    );
}
