import { RiCalendarLine } from '@remixicon/react';
import { format, parseISO } from 'date-fns';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type Props = {
    id: string;
    start: string;
    end: string;
    onChange: (start: string, end: string) => void;
    invalid?: boolean;
};

export function DateRangePicker({ id, start, end, onChange, invalid }: Props) {
    const [open, setOpen] = useState(false);
    const from = start ? parseISO(start) : undefined;
    const to = end ? parseISO(end) : undefined;

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    aria-invalid={invalid}
                    className={cn(
                        'w-full justify-start text-left font-normal',
                        !from && !to && 'text-muted-foreground',
                    )}
                >
                    <RiCalendarLine aria-hidden="true" />
                    {from
                        ? `${format(from, 'dd/MM/yyyy')} – ${to ? format(to, 'dd/MM/yyyy') : 'Select end date'}`
                        : to
                          ? `Until ${format(to, 'dd/MM/yyyy')}`
                          : 'Select date range'}
                </Button>
            </PopoverTrigger>
            <PopoverContent
                align="start"
                className="w-auto max-w-[calc(100vw-2rem)] p-0"
            >
                <Calendar
                    mode="range"
                    autoFocus
                    defaultMonth={from ?? to}
                    selected={from || to ? { from, to } : undefined}
                    onSelect={(range) => {
                        onChange(
                            range?.from ? format(range.from, 'yyyy-MM-dd') : '',
                            range?.to ? format(range.to, 'yyyy-MM-dd') : '',
                        );
                    }}
                />
                <div className="flex justify-between border-t p-3">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => onChange('', '')}
                    >
                        Clear
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        onClick={() => setOpen(false)}
                    >
                        Done
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}
