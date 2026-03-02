<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Pencil, Trash2 } from 'lucide-vue-next';

defineProps<{
    gymClasses: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
    };
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Gym Class', href: '/master/gym-class' },
];

const deleteGymClass = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus kelas gym ini?')) {
        router.delete(`/master/gym-class/${id}`);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Master Gym Class" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-6">

                <div class="flex items-center justify-between">
                    <Heading title="Daftar Kelas Gym" description="Kelola kelas yang tersedia untuk jadwal dan booking." />
                    <Link href="/master/gym-class/create"
                        class="rounded-xl bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90">
                        Tambah Kelas
                    </Link>
                </div>

                <div class="rounded-xl border bg-background overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/50 text-muted-foreground font-medium border-b">
                            <tr>
                                <th class="px-6 py-4">Gym</th>
                                <th class="px-6 py-4">Nama Kelas</th>
                                <th class="px-6 py-4">Kategori</th>
                                <th class="px-6 py-4">Kapasitas</th>
                                <th class="px-6 py-4">Durasi</th>
                                <th class="px-6 py-4">Jadwal</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="item in gymClasses.data" :key="item.id" class="hover:bg-muted/20 transition-colors">
                                <td class="px-6 py-4 font-medium">{{ item.gym?.name }}</td>
                                <td class="px-6 py-4">{{ item.name }}</td>
                                <td class="px-6 py-4">
                                    <span v-if="item.category" class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs">
                                        {{ item.category }}
                                    </span>
                                    <span v-else class="text-muted-foreground text-xs italic">-</span>
                                </td>
                                <td class="px-6 py-4">{{ item.default_capacity }} orang</td>
                                <td class="px-6 py-4">{{ item.duration_minutes }} menit</td>
                                <td class="px-6 py-4">{{ item.class_schedules_count ?? 0 }} jadwal</td>
                                <td class="px-6 py-4">
                                    <span :class="item.is_active
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-red-100 text-red-700'"
                                        class="px-2 py-0.5 rounded-full text-xs font-medium">
                                        {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center gap-3">
                                        <Link :href="`/master/gym-class/${item.id}/edit`"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-yellow-100 text-yellow-600 hover:bg-yellow-500 hover:text-white transition"
                                            title="Edit Kelas">
                                            <Pencil :size="16" />
                                        </Link>
                                        <button @click="deleteGymClass(item.id)" type="button"
                                            class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition"
                                            title="Hapus Kelas">
                                            <Trash2 :size="16" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="gymClasses.data.length === 0">
                                <td colspan="8" class="px-6 py-10 text-center text-muted-foreground">
                                    Belum ada data kelas gym.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="gymClasses.last_page > 1" class="flex justify-center gap-2 mt-4">
                    <template v-for="link in gymClasses.links" :key="link.label">
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
