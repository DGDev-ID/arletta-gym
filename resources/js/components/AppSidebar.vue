<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, CreditCard, Dumbbell, History, LayoutGrid, Package, ShieldCheck, UserCog, Users, BookOpen, ScanLine } from 'lucide-vue-next';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
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
import management from '@/routes/management';
import master from '@/routes/master';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';
import transaction from '@/routes/transaction';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        roles: ["Super Admin", "Admin"]
    },
];

const masterDataNavItems: NavItem[] = [
    {
        title: "Gym",
        href: master.gym.index(),
        icon: Dumbbell,
        roles: ["Super Admin"]
    },
    {
        title: "Membership",
        href: master.membership.index(),
        icon: CreditCard, 
        roles: ["Super Admin"]
    },
    {
        title: "Personal Trainer Package",
        href: master.personalTrainerPackage.index(),
        icon: Package, 
        roles: ["Super Admin"]
    },
    {
        title: "Gym Class",
        href: '/master/gym-class',
        icon: BookOpen,
        roles: ["Super Admin", "Admin"]
    },
    {
        title: "Class Schedule",
        href: '/master/class-schedule',
        icon: CalendarDays,
        roles: ["Super Admin", "Admin"]
    },
    {
        title: "Product",
        href: '/master/product',
        icon: Package,
        roles: ["Super Admin", "Admin"]
    },
];

const managementNavItems: NavItem[] = [
    {
        title: "User",
        href: management.user.index(),
        icon: Users, 
        roles: ["Super Admin", "Admin"]
    },
    {
        title: "Reschedule",
        href: '/management/reschedule',
        icon: CalendarDays,
        roles: ["Super Admin", "Admin"]
    },
    {
        title: "Admin",
        href: management.admin.index(),
        icon: ShieldCheck,
        roles: ["Super Admin"]
    },
    {
        title: "Personal Trainer",
        href: management.personalTrainer.index(),
        icon: UserCog, 
        roles: ["Super Admin", "Admin"]
    },
];

const transactionNavItems: NavItem[] = [
    {
        title: "History Transaction",
        href: transaction.history.index(),
        icon: History, 
        roles: ["Super Admin", "Admin"]
    },
    {
        title: "Transaction Per Session",
        href: transaction.transactionPerSession.index(),
        icon: CreditCard,
        roles: ["Super Admin", "Admin"]
    },
    {
        title: "Kasir Pembayaran",
        href: '/transaction/pos',
        icon: CreditCard,
        roles: ["Super Admin", "Admin"]
    },
];

const scanNavItems: NavItem[] = [
    {
        title: "Scan QR Code",
        href: '/scan-qr',
        icon: ScanLine,
        roles: ["Super Admin", "Admin"]
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain title="Dashboard" :items="mainNavItems" />
            <NavMain title="Master Data" :items="masterDataNavItems" />
            <NavMain title="Management" :items="managementNavItems" />
            <NavMain title="Transaction" :items="transactionNavItems" />
            <NavMain title="Scan" :items="scanNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
