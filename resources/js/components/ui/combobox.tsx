import { useRef, useState } from 'react';
import { RiExpandUpDownLine } from '@remixicon/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

type Props = {
    id: string;
    label: string;
    searchPlaceholder?: string;
    options: Record<string, string>;
    value: string;
    onChange: (value: string) => void;
    invalid?: boolean;
    disabled?: boolean;
};

export function Combobox({
    id: fieldId,
    label: fieldLabel,
    searchPlaceholder,
    options,
    value,
    onChange,
    invalid,
    disabled,
}: Props) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [active, setActive] = useState(0);
    const input = useRef<HTMLInputElement>(null);
    const matches = Object.entries(options).filter(([, label]) =>
        label.toLowerCase().includes(search.toLowerCase()),
    );
    function select(id: string) {
        onChange(id);
        setOpen(false);
    }
    return (
        <Popover
            open={open}
            onOpenChange={(next) => {
                setOpen(next);
                setSearch('');
                setActive(0);
            }}
        >
            <PopoverTrigger asChild>
                <Button
                    id={fieldId}
                    type="button"
                    variant="outline"
                    aria-haspopup="listbox"
                    aria-expanded={open}
                    aria-controls={open ? `${fieldId}-options` : undefined}
                    aria-invalid={invalid}
                    disabled={disabled}
                    className="h-auto min-h-9 w-full justify-between text-left font-normal"
                >
                    <span className="min-w-0 whitespace-normal">
                        {options[value] ?? `Choose ${fieldLabel}`}
                    </span>
                    <RiExpandUpDownLine
                        className="ml-2 size-4 shrink-0"
                        aria-hidden="true"
                    />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                align="start"
                className="w-[var(--radix-popover-trigger-width)] p-2"
                onOpenAutoFocus={(event) => {
                    event.preventDefault();
                    input.current?.focus();
                }}
            >
                <Input
                    ref={input}
                    role="combobox"
                    aria-label={`Search ${fieldLabel}`}
                    aria-autocomplete="list"
                    aria-expanded={open}
                    aria-controls={`${fieldId}-options`}
                    aria-activedescendant={
                        matches[active]
                            ? `${fieldId}-option-${matches[active][0]}`
                            : undefined
                    }
                    placeholder={searchPlaceholder ?? `Search ${fieldLabel.toLowerCase()}…`}
                    value={search}
                    onChange={(event) => {
                        setSearch(event.target.value);
                        setActive(0);
                    }}
                    onKeyDown={(event) => {
                        if (
                            event.key === 'ArrowDown' ||
                            event.key === 'ArrowUp'
                        ) {
                            event.preventDefault();
                            const next = matches.length
                                ? (active +
                                      (event.key === 'ArrowDown' ? 1 : -1) +
                                      matches.length) %
                                  matches.length
                                : 0;
                            setActive(next);
                            if (matches[next])
                                document
                                    .getElementById(
                                        `${fieldId}-option-${matches[next][0]}`,
                                    )
                                    ?.scrollIntoView({ block: 'nearest' });
                        } else if (event.key === 'Enter') {
                            event.preventDefault();
                            if (matches[active]) select(matches[active][0]);
                        }
                    }}
                />
                <div
                    id={`${fieldId}-options`}
                    role="listbox"
                    aria-label={fieldLabel}
                    className="mt-2 max-h-64 overflow-y-auto"
                >
                    {matches.map(([id, label], index) => (
                        <div
                            key={id}
                            id={`${fieldId}-option-${id}`}
                            role="option"
                            aria-selected={value === id}
                            onMouseEnter={() => setActive(index)}
                            onClick={() => select(id)}
                            className={`cursor-pointer rounded-sm px-3 py-2 text-sm ${index === active ? 'bg-accent text-accent-foreground' : ''}`}
                        >
                            {label}
                            {value === id && (
                                <span className="ml-2" aria-hidden="true">
                                    ✓
                                </span>
                            )}
                        </div>
                    ))}
                    {!matches.length && (
                        <p
                            role="status"
                            className="p-3 text-sm text-muted-foreground"
                        >
                            No matches found.
                        </p>
                    )}
                </div>
            </PopoverContent>
        </Popover>
    );
}
