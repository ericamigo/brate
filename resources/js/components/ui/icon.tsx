import type { RemixiconComponentType } from '@remixicon/react';

interface IconProps {
    iconNode?: RemixiconComponentType | null;
    className?: string;
}

export function Icon({ iconNode: IconComponent, className }: IconProps) {
    if (!IconComponent) {
        return null;
    }

    return <IconComponent className={className} />;
}
