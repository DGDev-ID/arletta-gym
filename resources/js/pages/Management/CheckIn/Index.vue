<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    checkIns: {
        data: any[];
        links: any[];
        total: number;
    };
    gyms: { id: number; name: string }[];
    filters: {
        search?: string;
        gym_id?: string;
        date?: string;
    };
}>();

const breadcrumbItems = [
    { title: 'Management', href: '#' },
    { title: 'Check In Member', href: '/management/check-in' },
];

const search = ref(props.filters.search || '');
const gymId = ref(props.filters.gym_id || '');
const date = ref(props.filters.date || '');

watch(() => props.filters, (newFilters) => {
    search.value = newFilters.search || '';
    gymId.value = newFilters.gym_id || '';
    date.value = newFilters.date || '';
}, { deep: true });

const applyFilters = debounce(() => {
    router.get('/management/check-in', {
        search: search.value,
        gym_id: gymId.value,
        date: date.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}, 300);

watch([search, gymId, date], applyFilters);

const resetFilters = () => {
    search.value = '';
    gymId.value = '';
    date.value = '';
};

const formatDateTime = (dateStr: string) => {
    return new Date(dateStr).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const membershipBadgeClass = (status: string) => {
    switch (status) {
        case 'active':  return 'bg-green-100 text-green-800 border-green-200';
        case 'freeze':  return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'expired': return 'bg-red-100 text-red-800 border-red-200';
        default:        return 'bg-muted text-muted-foreground border-border';
    }
};

const membershipLabel = (status: string) => {
    switch (status) {
        case 'active':  return 'Aktif';
        case 'freeze':  return 'Freeze';
        case 'expired': return 'Expired';
        default:        return status ?? '-';
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Check In Member" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <div class="flex flex-col justify-between gap-4">
                    <div class="flex flex-row justify-between items-center">
                        <Heading
                            title="Check In Member"
                            :description="`Data member yang berhasil scan QR Code. Total: ${checkIns.total}`"
                        />
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 w-full justify-between">
                        <!-- Gym filter -->
                        <div class="w-full sm:w-56">
                            <select
                                v-model="gymId"
                                class="flex h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">Semua Gym</option>
                                <option v-for="gym in gyms" :key="gym.id" :value="gym.id">
                                    {{ gym.name }}
                                </option>
                            </select>
                        </div>

                    <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                        <!-- Date filter -->
                        <div class="w-full sm:w-48">
                            <input
                                v-model="date"
                                type="date"
                                class="flex h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            />
                        </div>

                        <!-- Search -->
                        <div class="relative w-full sm:w-64">
                            <Input
                                v-model="search"
                                placeholder="Cari nama / email..."
                                class="rounded-xl pr-10"
                            />
                        </div>

                        <!-- Reset -->
                        <div v-if="search || gymId || date">
                            <button
                                @click="resetFilters"
                                class="h-10 cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl border border-input bg-background px-4 py-2 text-sm font-medium text-muted-foreground shadow-sm transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                Reset Filter
                            </button>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-background overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-muted/50 text-muted-foreground font-medium border-b">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Member</th>
                                    <th scope="col" class="px-6 py-4">Gym</th>
                                    <th scope="col" class="px-6 py-4">Status Membership</th>
                                    <th scope="col" class="px-6 py-4">Sisa Hari</th>
                                    <th scope="col" class="px-6 py-4">Waktu Check In</th>
                                    <th scope="col" class="px-6 py-4">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="item in checkIns.data"
                                    :key="item.id"
                                    class="hover:bg-muted/20 transition-colors"
                                >
                                    <!-- Member column -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-primary/10 text-primary rounded-full flex items-center justify-center font-bold uppercase">
                                                {{ item.user?.name?.charAt(0) ?? '?' }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium">{{ item.user?.name ?? '-' }}</div>
                                                <div class="text-xs text-muted-foreground">{{ item.user?.email ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Gym column -->
                                    <td class="px-6 py-4 whitespace-nowrap text-muted-foreground">
                                        {{ item.gym?.name ?? '-' }}
                                    </td>

                                    <!-- Membership status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 py-1 inline-flex text-[10px] uppercase tracking-wider font-bold rounded-full border"
                                            :class="membershipBadgeClass(item.membership_status)"
                                        >
                                            {{ membershipLabel(item.membership_status) }}
                                        </span>
                                    </td>

                                    <!-- Days remaining -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="item.days_remaining !== null && item.days_remaining !== undefined" class="font-medium" :class="item.days_remaining <= 0 ? 'text-red-500' : item.days_remaining <= 7 ? 'text-yellow-500' : 'text-green-600'">
                                            {{ item.days_remaining <= 0 ? 'Habis' : item.days_remaining + ' hari' }}
                                        </span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>

                                    <!-- Check-in time -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-[10px] uppercase tracking-wider font-bold rounded-full border bg-green-100 text-green-800 border-green-200">
                                            {{ formatDateTime(item.created_at) }}
                                        </span>
                                    </td>

                                    <!-- Description -->
                                    <td class="px-6 py-4 text-muted-foreground text-xs">
                                        {{ item.description ?? '-' }}
                                    </td>
                                </tr>

                                <!-- Empty state -->
                                <tr v-if="checkIns.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-muted-foreground">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-muted-foreground/50 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            <span class="text-lg font-medium">Tidak ada data check in</span>
                                            <span class="text-sm">Coba ubah filter pencarian.</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 bg-background">
                        <Pagination :links="checkIns.links" />
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
