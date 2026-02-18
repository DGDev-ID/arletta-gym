<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, CreditCard, Dumbbell, Folder, History, LayoutGrid, Package, ShieldCheck, UserCog, Users } from 'lucide-vue-next';
import NavFooter from '@/components/NavFooter.vue';
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
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';
import { dashboard } from '@/routes';
import gym from '@/routes/master/gym';
import membership from '@/routes/master/membership';

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
        href: gym.index(),
        icon: Dumbbell,
    },
    {
        title: "Membership",
        href: membership.index(),
        icon: CreditCard, 
    },
    {
        title: "Personal Trainer Package",
        href: "#",
        icon: Package, 
    },
];

const managementNavItems: NavItem[] = [
    {
        title: "Admin",
        href: "#",
        icon: ShieldCheck,
    },
    {
        title: "User",
        href: "#",
        icon: Users, 
    },
    {
        title: "Personal Trainer",
        href: "#",
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

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
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
