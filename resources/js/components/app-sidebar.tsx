import { Link } from '@inertiajs/react';
import {
    BookOpen,
    Database,
    Eye,
    FolderGit2,
    LayoutGrid,
    MapPinned,
    Package,
    Tags,
    Users,
} from 'lucide-react';
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
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Koperasi',
        href: '/admin/koperasis',
        icon: MapPinned,
    },
    {
        title: 'Sarpras',
        href: '/admin/sarprases',
        icon: Package,
    },
    {
        title: 'Sarpras Koperasi',
        href: '/admin/koperasi-sarprases',
        icon: Database,
    },
    {
        title: 'Kategori Pengaduan',
        href: '/admin/pengaduan-categories',
        icon: Tags,
    },
    {
        title: 'Pengaduan',
        href: '/admin/pengaduans',
        icon: BookOpen,
    },
    {
        title: 'User',
        href: '/admin/users',
        icon: Users,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'View Website',
        href: '/',
        icon: Eye,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
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
