import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { DateRangePicker } from '@/components/date-range-picker';
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
import { Shell } from '@/components/page-shell';
const controlClass =
    'w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';

type Field = {
    name: string;
    label: string;
    type: string;
    required: boolean;
    options?: Record<string, string>;
};
const title = 'Companies';
const singular = 'Company';
const resource = 'companies';

type Props = {
    record: { id?: number; [key: string]: unknown };
    fields: Field[];
};
export default function Edit({ record, fields }: Props) {
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
        form.put(`/${resource}/${record.id}`);
    }
    return (
        <Shell title={`${'Edit'} ${singular}`}>
            <Link
                href={`/${resource}`}
                className="text-sm text-muted-foreground"
            >
                ← {title}
            </Link>
            <h1 className="my-6 text-2xl font-semibold tracking-tight">
                {'Edit'} {singular}
            </h1>
            <form
                onSubmit={submit}
                className="grid max-w-2xl gap-5 rounded-lg border bg-card p-6"
            >
                {fields
                    .filter((field) => field.name !== 'period_end')
                    .map((field) =>
                        field.name === 'period_start' ? (
                            <div key={field.name} className="grid gap-2">
                                <Label htmlFor="billing-period">
                                    Billing period *
                                </Label>
                                <DateRangePicker
                                    id="billing-period"
                                    start={form.data.period_start}
                                    end={form.data.period_end}
                                    onChange={(start, end) =>
                                        form.setData((data) => ({
                                            ...data,
                                            period_start: start,
                                            period_end: end,
                                        }))
                                    }
                                    invalid={Boolean(
                                        form.errors.period_start ||
                                        form.errors.period_end,
                                    )}
                                />
                                <InputError
                                    message={form.errors.period_start}
                                />
                                <InputError message={form.errors.period_end} />
                            </div>
                        ) : (
                            <div key={field.name} className="grid gap-2">
                                <Label htmlFor={field.name}>
                                    {field.label}
                                    {field.required && ' *'}
                                </Label>
                                {field.type === 'select' ? (
                                    <Select
                                        name={field.name}
                                        value={
                                            form.data[field.name] ||
                                            (field.required ? '' : 'none')
                                        }
                                        onValueChange={(value) =>
                                            form.setData(
                                                field.name,
                                                value === 'none' ? '' : value,
                                            )
                                        }
                                        required={field.required}
                                    >
                                        <SelectTrigger
                                            id={field.name}
                                            className="w-full"
                                            aria-invalid={Boolean(
                                                form.errors[field.name],
                                            )}
                                        >
                                            <SelectValue
                                                placeholder={`Choose ${field.label}`}
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                value="none"
                                                disabled={field.required}
                                            >
                                                Choose {field.label}
                                            </SelectItem>
                                            {Object.entries(
                                                field.options ?? {},
                                            ).map(([id, label]) => (
                                                <SelectItem key={id} value={id}>
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                ) : field.type === 'textarea' ? (
                                    <textarea
                                        id={field.name}
                                        value={form.data[field.name]}
                                        onChange={(event) =>
                                            form.setData(
                                                field.name,
                                                event.target.value,
                                            )
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
                                            form.setData(
                                                field.name,
                                                event.target.value,
                                            )
                                        }
                                        required={field.required}
                                        step={
                                            field.type === 'number'
                                                ? '0.01'
                                                : undefined
                                        }
                                        min={
                                            field.type === 'number'
                                                ? 0
                                                : undefined
                                        }
                                    />
                                )}
                                <InputError message={form.errors[field.name]} />
                            </div>
                        ),
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
