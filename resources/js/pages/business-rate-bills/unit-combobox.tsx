import { Combobox } from '@/components/ui/combobox';
import type { ComponentProps } from 'react';

export function UnitCombobox(
    props: Omit<
        ComponentProps<typeof Combobox>,
        'id' | 'label' | 'searchPlaceholder'
    >,
) {
    return (
        <Combobox
            {...props}
            id="unit_id"
            label="Unit"
            searchPlaceholder="Search company, building or unit…"
        />
    );
}
