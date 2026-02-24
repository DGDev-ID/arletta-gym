<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CreditCard, Dumbbell, History, LayoutGrid, Package, ShieldCheck, UserCog, Users } from 'lucide-vue-next';
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

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const masterDataNavItems: NavItem[] = [
    {
        title: "Gym",
        href: master.gym.index(),
        icon: Dumbbell,
    },
    {
        title: "Membership",
        href: master.membership.index(),
        icon: CreditCard, 
    },
    {
        title: "Personal Trainer Package",
        href: master.personalTrainerPackage.index(),
        icon: Package, 
    },
];

const managementNavItems: NavItem[] = [
    {
        title: "User",
        href: management.user.index(),
        icon: Users, 
    },
    {
        title: "Admin",
        href: management.admin.index(),
        icon: ShieldCheck,
    },
    {
        title: "Personal Trainer",
        href: management.personalTrainer.index(),
        icon: UserCog, 
    },
];

const transactionNavItems: NavItem[] = [
    {
        title: "History Transaction",
        href: "#",
        icon: History, 
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
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
