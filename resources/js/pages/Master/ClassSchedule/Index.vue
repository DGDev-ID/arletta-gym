<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Pencil, Trash2, Users } from 'lucide-vue-next';

const DAY_NAMES = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

defineProps<{
    schedules: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
    };
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Class Schedule', href: '/master/class-schedule' },
];

const deleteSchedule = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {
        router.delete(`/master/class-schedule/${id}`);
    }
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Master Class Schedule" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-6">

                <div class="flex items-center justify-between">
                    <Heading title="Jadwal Kelas" description="Kelola jadwal kelas gym beserta trainer dan kapasitas." />
                    <Link href="/master/class-schedule/create"
                        class="rounded-xl bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90">
                        Tambah Jadwal
                    </Link>
                </div>

                <div class="rounded-xl border bg-background overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/50 text-muted-foreground font-medium border-b">
                            <tr>
                                <th class="px-6 py-4">Kelas</th>
                                <th class="px-6 py-4">Gym</th>
                                <th class="px-6 py-4">Trainer</th>
                                <th class="px-6 py-4">Tanggal</th>
                                <th class="px-6 py-4">Waktu</th>
                                <th class="px-6 py-4">Lokasi</th>
                                <th class="px-6 py-4">Kapasitas</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="item in schedules.data" :key="item.id" class="hover:bg-muted/20 transition-colors">
                                <td class="px-6 py-4 font-medium">
                                    {{ item.gym_class?.name }}
                                    <span v-if="item.is_recurring"
                                        class="ml-1 px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 text-xs font-medium"
                                        :title="`Berulang setiap ${DAY_NAMES[item.recurring_day_of_week] ?? ''}`">
                                        🔁 {{ DAY_NAMES[item.recurring_day_of_week] ?? '' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">{{ item.gym_class?.gym?.name }}</td>
                                <td class="px-6 py-4">
                                    {{ item.trainer?.name || item.trainer_name || '-' }}
                                </td>
                                <td class="px-6 py-4">{{ formatDate(item.date) }}</td>
                                <td class="px-6 py-4">{{ item.start_time }} - {{ item.end_time }}</td>
                                <td class="px-6 py-4">{{ item.location || '-' }}</td>
                                <td class="px-6 py-4">
                                    <span :class="item.booked_count >= item.capacity ? 'text-red-600 font-semibold' : ''">
                                        {{ item.booked_count }}/{{ item.capacity }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="item.is_cancelled"
                                        class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-medium">
                                        Dibatalkan
                                    </span>
                                    <span v-else-if="item.zoom_link"
                                        class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 text-xs font-medium">
                                        Online
                                    </span>
                                    <span v-else
                                        class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                                        Aktif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center gap-3">
                                        <Link :href="`/master/class-schedule/${item.id}`"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-100 text-blue-600 hover:bg-blue-500 hover:text-white transition"
                                            title="Peserta Kelas">
                                            <Users :size="16" />
                                        </Link>
                                        <Link :href="`/master/class-schedule/${item.id}/edit`"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-yellow-100 text-yellow-600 hover:bg-yellow-500 hover:text-white transition"
                                            title="Edit Jadwal">
                                            <Pencil :size="16" />
                                        </Link>
                                        <button @click="deleteSchedule(item.id)" type="button"
                                            class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition"
                                            title="Hapus Jadwal">
                                            <Trash2 :size="16" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="schedules.data.length === 0">
                                <td colspan="9" class="px-6 py-10 text-center text-muted-foreground">
                                    Belum ada data jadwal kelas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="schedules.last_page > 1" class="flex justify-center gap-2 mt-4">
                    <template v-for="link in schedules.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url"
                            class="px-3 py-1.5 rounded-lg text-sm border transition"
                            :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                            v-html="link.label" />
                        <span v-else class="px-3 py-1.5 text-sm text-muted-foreground" v-html="link.label" />
                    </template>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

