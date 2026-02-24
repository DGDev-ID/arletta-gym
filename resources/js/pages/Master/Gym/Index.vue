<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { Gym, PaginatedData } from '@/types';
import { Pencil, Trash2 } from 'lucide-vue-next';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Master Gyms',
        href: '/master/gym',
    },
];

const deleteGym = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus gym ini?')) {
        router.delete(`/master/gym/${id}`);
    }
};

defineProps<{
    gyms: PaginatedData<Gym>;
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Master Gyms" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <Heading variant="small" title="Master Gyms"
                        description="Kelola daftar gym, lokasi, dan administrator." />

                    <Link href="/master/gym/create"
                        class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:opacity-90">
                        Tambah Gym
                    </Link>
                </div>

                <!-- Table Card -->
                <div class="rounded-2xl border bg-background shadow-sm overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr class="text-muted-foreground">
                                <th class="px-6 py-4 text-left font-medium">Nama Gym</th>
                                <th class="px-6 py-4 text-left font-medium">Alamat</th>
                                <th class="px-6 py-4 text-left font-medium">Mulai Akses</th>
                                <th class="px-6 py-4 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="gym in gyms.data" :key="gym.id" class="border-t hover:bg-muted/40 transition">
                                <td class="px-6 py-4 font-medium">
                                    {{ gym.name }}
                                </td>

                                <td class="px-6 py-4 text-muted-foreground truncate max-w-xs">
                                    {{ gym.address }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-muted px-3 py-1 text-xs">
                                        {{ gym.start_access }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center gap-3">
                                        <Link :href="`/master/gym/${gym.id}/edit`"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-yellow-100 text-yellow-600 hover:bg-yellow-500 hover:text-white transition"
                                            title="Edit Gym">
                                            <Pencil :size="16" />
                                        </Link>

                                        <button @click="deleteGym(gym.id)" type="button"
                                            class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition"
                                            title="Hapus Gym">
                                            <Trash2 :size="16" />
                                        </button>

                                    </div>
                                </td>
                            </tr>

                            <tr v-if="gyms.data.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-muted-foreground">
                                    Belum ada data gym.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
