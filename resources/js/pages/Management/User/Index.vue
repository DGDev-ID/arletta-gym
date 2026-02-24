<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { Settings } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue'; // Pastikan path sesuai
import Pagination from '@/components/Pagination.vue';
import Input from "@/components/ui/input/Input.vue"; // Pastikan path sesuai
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    users: {
        data: any[];
        links: any[];
        total: number;
    };
    filters: {
        search?: string;
        role?: string;
    };
}>();

const breadcrumbItems = [
    { title: 'Management User', href: '/management/users' },
];

const search = ref(props.filters.search || '');
const role = ref(props.filters.role || '');

watch(() => props.filters, (newFilters) => {
    search.value = newFilters.search || '';
    role.value = newFilters.role || '';
}, { deep: true });

watch(
    [search, role],
    debounce(([newSearch, newRole]) => {
        router.get('/management/user', {
            search: newSearch,
            role: newRole
        }, {
            preserveState: true,
            replace: true,
            preserveScroll: true
        });
    }, 300)
);

const getRoleBadgeClass = (roleName: string) => {
    switch (roleName) {
        case 'Super Admin': return 'bg-red-100 text-red-800 border-red-200';
        case 'Admin': return 'bg-orange-100 text-orange-800 border-orange-200';
        case 'Personal Trainer': return 'bg-blue-100 text-blue-800 border-blue-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Manage Users" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <div class="flex flex-col justify-between gap-4">
                    <div class="flex flex-row justify-between item-center">
                        <Heading title="Manage Users"
                            :description="`Kelola data pengguna terdaftar. Total Users: ${users.total}`" />

                        <div>
                            <Link href="/management/user/create"
                                class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                            Tambah User
                            </Link>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 w-full md:flex-1 justify-between">
                        <div class="w-full sm:w-48">
                            <select v-model="role"
                                class="flex h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="">All Roles</option>
                                <option value="Super Admin">Super Admin</option>
                                <option value="Admin">Admin</option>
                                <option value="Personal Trainer">Personal Trainer</option>
                                <option value="User">User</option>
                            </select>
                        </div>

                        <div class="relative w-full sm:w-64">
                            <Input v-model="search" placeholder="Ketik minimal 3 karakter..."
                                class="rounded-xl pr-10" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-background overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-muted/50 text-muted-foreground font-medium border-b">
                                <tr>
                                    <th scope="col" class="px-6 py-4">User Details</th>
                                    <th scope="col" class="px-6 py-4">Role</th>
                                    <th scope="col" class="px-6 py-4">Joined Date</th>
                                    <th scope="col" class="px-6 py-4"><div class="flex justify-center items-center">Action</div></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="user in users.data" :key="user.id"
                                    class="hover:bg-muted/20 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="flex-shrink-0 h-10 w-10 bg-primary/10 text-primary rounded-full flex items-center justify-center font-bold">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium">{{ user.name }}</div>
                                                <div class="text-xs text-muted-foreground">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex gap-1">
                                            <span v-for="(role, index) in user.roles" :key="index"
                                                class="px-2 py-1 inline-flex text-[10px] uppercase tracking-wider font-bold rounded-full border"
                                                :class="getRoleBadgeClass(role.name)">
                                                {{ role.name }}
                                            </span>
                                            <span v-if="user.roles.length === 0"
                                                class="text-muted-foreground text-xs italic">
                                                No Role
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-muted-foreground">
                                        {{ new Date(user.created_at).toLocaleDateString() }}
                                    </td>

                                    <td>
                                        <div class="flex justify-center items-center">
                                            <Link :href="`/management/user/${user.id}/edit`"
                                                class="inline-flex items-center justify-center rounded-lg bg-primary p-2 text-primary-foreground shadow-sm transition-all hover:bg-primary/90 focus-visible:outline focus-visible:outline-2 focus-visible:ring-ring cursor-pointer"
                                                title="Edit User">
                                            <Settings :size="18" stroke-width="2" />
                                            </Link>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="users.data.length === 0">
                                    <td colspan="3" class="px-6 py-12 text-center text-muted-foreground">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-muted-foreground/50 mb-3" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                                </path>
                                            </svg>
                                            <span class="text-lg font-medium">No users found</span>
                                            <span class="text-sm">Try adjusting your search or filters.</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 bg-background">
                        <Pagination :links="users.links" />
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>