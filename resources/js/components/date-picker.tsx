import { format, parseISO } from 'date-fns';
import { useState } from 'react';
import { RiCalendarLine } from '@remixicon/react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

type Props = {
    id: string;
    value: string;
    onChange: (value: string) => void;
    invalid?: boolean;
    disabled?: boolean;
};

export function DatePicker({ id, value, onChange, invalid, disabled }: Props) {
    const [open, setOpen] = useState(false);
    const selected = value ? parseISO(value) : undefined;
    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    aria-invalid={invalid}
                    className="w-full justify-start text-left font-normal"
                >
                    <RiCalendarLine aria-hidden="true" />
                    {selected ? format(selected, 'dd/MM/yyyy') : 'dd/mm/yyyy'}
                </Button>
            </PopoverTrigger>
            <PopoverContent align="start" className="w-auto p-0">
                <Calendar
                    mode="single"
                    autoFocus
                    selected={selected}
                    defaultMonth={selected}
                    onSelect={(day) => {
                        onChange(day ? format(day, 'yyyy-MM-dd') : '');
                        setOpen(false);
                    }}
                />
                <div className="border-t p-3">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            onChange('');
                            setOpen(false);
                        }}
                    >
                        Clear
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}
