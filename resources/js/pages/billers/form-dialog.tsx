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

type Props = { record?: { id: number; [key: string]: unknown } };

export function FormDialog({ record }: Props) {
    const [open, setOpen] = useState(false);
    const form = useForm({ name: '' });
    function changeOpen(next: boolean) {
        if (form.processing) return;
        setOpen(next);
        form.clearErrors();
        form.setData({ name: record?.name == null ? '' : String(record.name) });
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
        if (record) form.put('/billers/' + record.id, options);
        else form.post('/billers', options);
    }
    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogTrigger asChild>
                <Button
                    variant={record ? 'outline' : 'default'}
                    size={record ? 'sm' : 'default'}
                >
                    {record ? 'Edit' : 'Add Biller'}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {record ? 'Edit Biller' : 'Add Biller'}
                    </DialogTitle>
                    <DialogDescription>
                        Enter the biller details below.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="billers-name">Name</Label>
                        <Input
                            id="billers-name"
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
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={form.processing}
                            onClick={() => changeOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button disabled={form.processing}>Save Biller</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
