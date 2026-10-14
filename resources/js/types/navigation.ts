import type { InertiaLinkProps } from '@inertiajs/react';
import type { RemixiconComponentType } from '@remixicon/react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: RemixiconComponentType | null;
    isActive?: boolean;
};
