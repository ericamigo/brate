import { DayPicker } from 'react-day-picker';
import type { ComponentProps } from 'react';
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export function Calendar({
    className,
    classNames,
    showOutsideDays = true,
    ...props
}: ComponentProps<typeof DayPicker>) {
    return (
        <DayPicker
            showOutsideDays={showOutsideDays}
            className={cn('relative p-3', className)}
            classNames={{
                months: 'relative flex flex-col gap-4 sm:flex-row',
                month: 'flex flex-col gap-4',
                month_caption: 'flex h-9 items-center justify-center',
                caption_label: 'text-sm font-medium',
                nav: 'absolute inset-x-0 top-0 flex justify-between',
                button_previous: cn(buttonVariants({ variant: 'outline' }), 'size-9 p-0'),
                button_next: cn(buttonVariants({ variant: 'outline' }), 'size-9 p-0'),
                chevron: 'size-4 fill-current',
                month_grid: 'w-full border-collapse',
                weekdays: 'flex',
                weekday: 'w-9 text-center text-xs font-normal text-muted-foreground',
                week: 'mt-2 flex',
                day: 'relative size-9 p-0 text-center text-sm',
                day_button: cn(buttonVariants({ variant: 'ghost' }), 'size-9 p-0 font-normal'),
                selected: '[&>button]:bg-primary [&>button]:text-primary-foreground',
                today: '[&>button]:border [&>button]:border-primary',
                outside: 'text-muted-foreground opacity-50',
                disabled: 'text-muted-foreground opacity-50',
                hidden: 'invisible',
                range_start: 'rounded-l-md bg-accent',
                range_end: 'rounded-r-md bg-accent',
                range_middle: 'bg-accent [&>button]:rounded-none [&>button]:bg-accent [&>button]:text-accent-foreground',
                ...classNames,
            }}
            {...props}
        />
    );
}
