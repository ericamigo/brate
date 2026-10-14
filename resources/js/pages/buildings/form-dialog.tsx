import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogTrigger,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Props = {
    record?: { id: number; [key: string]: unknown };
    companies: { id: number; name: string }[];
};

export function FormDialog({ record, companies }: Props) {
    const [open, setOpen] = useState(false);
    const form = useForm({ company_id: '', name: '', address: '' });
    function changeOpen(next: boolean) {
        if (form.processing) return;
        setOpen(next);
        form.clearErrors();
        form.setData({
            company_id:
                record?.company_id == null ? '' : String(record.company_id),
            name: record?.name == null ? '' : String(record.name),
            address: record?.address == null ? '' : String(record.address),
        });
    }
    function submit(event: FormEvent) {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                form.reset();
                form.clearErrors();
            },
        };
        if (record) form.put('/buildings/' + record.id, options);
        else form.post('/buildings', options);
    }
    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogTrigger asChild>
                <Button
                    variant={record ? 'outline' : 'default'}
                    size={record ? 'sm' : 'default'}
                >
                    {record ? 'Edit' : 'Add Building'}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {record ? 'Edit Building' : 'Add Building'}
                    </DialogTitle>
                    <DialogDescription>
                        Enter the building details below.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="buildings-company_id">Company</Label>
                        <Select
                            value={form.data.company_id}
                            onValueChange={(value) =>
                                form.setData('company_id', value)
                            }
                            required
                            disabled={form.processing}
                        >
                            <SelectTrigger
                                id="buildings-company_id"
                                className="w-full"
                                aria-invalid={!!form.errors.company_id}
                            >
                                <SelectValue placeholder="Choose Company" />
                            </SelectTrigger>
                            <SelectContent>
                                {companies.map((item) => (
                                    <SelectItem
                                        key={item.id}
                                        value={String(item.id)}
                                    >
                                        {item.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.company_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="buildings-name">Name</Label>
                        <Input
                            id="buildings-name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            required={true}
                            maxLength={255}
                            disabled={form.processing}
                            aria-invalid={!!form.errors.name}
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="buildings-address">Address</Label>
                        <Input
                            id="buildings-address"
                            value={form.data.address}
                            onChange={(event) =>
                                form.setData('address', event.target.value)
                            }
                            required={false}
                            maxLength={undefined}
                            disabled={form.processing}
                            aria-invalid={!!form.errors.address}
                        />
                        <InputError message={form.errors.address} />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={form.processing}
                            onClick={() => changeOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button disabled={form.processing}>
                            Save Building
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
