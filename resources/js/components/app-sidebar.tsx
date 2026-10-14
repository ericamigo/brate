import { route } from 'ziggy-js';
import { Link } from '@inertiajs/react';
import {
    RiBookOpenLine as BookOpen,
    RiGitRepositoryLine as FolderGit2,
    RiBarChartLine,
    RiBuildingLine,
    RiBuilding2Line,
    RiDoorOpenLine,
    RiGroupLine,
    RiKeyLine,
    RiBankLine,
    RiBillLine,
    RiFileTextLine,
    RiUploadLine,
} from '@remixicon/react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    { title: 'Occupancy Report', href: route('home'), icon: RiBarChartLine },
    {
        title: 'Companies',
        href: route('companies.index'),
        icon: RiBuildingLine,
    },
    {
        title: 'Buildings',
        href: route('buildings.index'),
        icon: RiBuilding2Line,
    },
    { title: 'Units', href: route('units.index'), icon: RiDoorOpenLine },
    { title: 'Tenants', href: route('tenants.index'), icon: RiGroupLine },
    { title: 'Tenancies', href: route('tenancies.index'), icon: RiKeyLine },
    { title: 'Billers', href: route('billers.index'), icon: RiBankLine },
    {
        title: 'Bills',
        href: route('business-rate-bills.index'),
        icon: RiBillLine,
    },
    {
        title: 'Documents',
        href: route('documents.index'),
        icon: RiFileTextLine,
    },
    { title: 'Import', href: route('imports.index'), icon: RiUploadLine },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={route('home')} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
