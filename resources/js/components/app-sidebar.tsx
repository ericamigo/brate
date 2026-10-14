import { route } from "ziggy-js";
import { Link } from "@inertiajs/react";
import {
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
} from "@remixicon/react";
import AppLogo from "@/components/app-logo";
import { NavMain } from "@/components/nav-main";
import { NavUser } from "@/components/nav-user";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";

import type { NavItem } from "@/types";

const mainNavGroups: { label: string; items: NavItem[] }[] = [
    {
        label: "Reports",
        items: [
            {
                title: "Occupancy Report",
                href: route("home"),
                icon: RiBarChartLine,
            },
        ],
    },
    {
        label: "Properties",
        items: [
            {
                title: "Units",
                href: route("units.index"),
                icon: RiDoorOpenLine,
            },
            {
                title: "Buildings",
                href: route("buildings.index"),
                icon: RiBuilding2Line,
            },
            {
                title: "Companies",
                href: route("companies.index"),
                icon: RiBuildingLine,
            },
        ],
    },
    {
        label: "Occupancy",
        items: [
            {
                title: "Tenancies",
                href: route("tenancies.index"),
                icon: RiKeyLine,
            },
            {
                title: "Tenants",
                href: route("tenants.index"),
                icon: RiGroupLine,
            },
        ],
    },
    {
        label: "Billing",
        items: [
            {
                title: "Bills",
                href: route("business-rate-bills.index"),
                icon: RiBillLine,
            },
            {
                title: "Billers",
                href: route("billers.index"),
                icon: RiBankLine,
            },
        ],
    },
    {
        label: "Files & Data",
        items: [
            {
                title: "Documents",
                href: route("documents.index"),
                icon: RiFileTextLine,
            },
            {
                title: "Import",
                href: route("imports.index"),
                icon: RiUploadLine,
            },
        ],
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={route("home")} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {mainNavGroups.map((group) => (
                    <NavMain
                        key={group.label}
                        label={group.label}
                        items={group.items}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
