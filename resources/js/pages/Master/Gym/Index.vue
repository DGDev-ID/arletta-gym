<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem } from '@/types';
import type { Gym, PaginatedData } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Master Gyms',
        href: '/master/gym',
    },
];

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
                        + Tambah Gym
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
                                    <Link :href="`/master/gym/${gym.id}/edit`"
                                        class="text-primary hover:underline text-sm font-medium">
                                        Edit
                                    </Link>
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
