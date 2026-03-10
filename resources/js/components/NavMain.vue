<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar'
import { useCurrentUrl } from '@/composables/useCurrentUrl'
import { type NavItem } from '@/types'

const props = defineProps<{
    items: NavItem[]
    title: string
}>()

const { isCurrentUrl } = useCurrentUrl()

const page = usePage()

const userRoles = computed(() => {
    return (page.props.auth?.roles ?? []) as string[]
})

const visibleItems = computed(() => {
    return props.items.filter(item => {

        if (!item.roles || item.roles.length === 0) {
            return true
        }

        return item.roles.some(role =>
            userRoles.value.includes(role)
        )
    })
})
</script>

<template>
    <SidebarGroup v-if="visibleItems.length" class="px-2 py-0">
        <SidebarGroupLabel>{{ title }}</SidebarGroupLabel>

        <SidebarMenu>
            <SidebarMenuItem
                v-for="item in visibleItems"
                :key="item.title"
            >
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component v-if="item.icon" :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>