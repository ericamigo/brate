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
    buildings: { id: number; name: string; company: { name: string } }[];
};

export function FormDialog({ record, buildings }: Props) {
    const [open, setOpen] = useState(false);
    const form = useForm({ building_id: '', name: '' });
    function changeOpen(next: boolean) {
        if (form.processing) return;
        setOpen(next);
        form.clearErrors();
        form.setData({
            building_id:
                record?.building_id == null ? '' : String(record.building_id),
            name: record?.name == null ? '' : String(record.name),
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
        if (record) form.put('/units/' + record.id, options);
        else form.post('/units', options);
    }
    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogTrigger asChild>
                <Button
                    variant={record ? 'outline' : 'default'}
                    size={record ? 'sm' : 'default'}
                >
                    {record ? 'Edit' : 'Add Unit'}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {record ? 'Edit Unit' : 'Add Unit'}
                    </DialogTitle>
                    <DialogDescription>
                        Enter the unit details below.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="units-building_id">Building</Label>
                        <Select
                            value={form.data.building_id}
                            onValueChange={(value) =>
                                form.setData('building_id', value)
                            }
                            required
                            disabled={form.processing}
                        >
                            <SelectTrigger
                                id="units-building_id"
                                className="w-full"
                                aria-invalid={!!form.errors.building_id}
                            >
                                <SelectValue placeholder="Choose Building" />
                            </SelectTrigger>
                            <SelectContent>
                                {buildings.map((item) => (
                                    <SelectItem
                                        key={item.id}
                                        value={String(item.id)}
                                    >{`${item.company.name} / ${item.name}`}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.building_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="units-name">Name</Label>
                        <Input
                            id="units-name"
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
                        <Button disabled={form.processing}>Save Unit</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
