<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Pencil, Trash2, Package2 } from 'lucide-vue-next';

defineProps<{
    bundle_packages: any[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Bundle Package', href: '/master/bundle-package' },
];

const deleteBundlePackage = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus paket bundling ini?')) {
        router.delete(`/master/bundle-package/${id}`);
    }
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const formatDays = (days: number) => {
    if (days % 30 === 0) {
        const months = days / 30;
        return `${months} Bulan (${days} Hari)`;
    }
    return `${days} Hari`;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Master Bundle Package" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-6">

                <div class="flex items-center justify-between">
                    <Heading title="Daftar Paket Bundling"
                        description="Kelola paket bundling yang menggabungkan membership dan sesi personal trainer." />
                    <Link href="/master/bundle-package/create"
                        class="rounded-xl bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90">
                        Tambah Paket Bundling
                    </Link>
                </div>

                <div class="rounded-xl border bg-background overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/50 text-muted-foreground font-medium border-b">
                            <tr>
                                <th class="px-6 py-4">Gym</th>
                                <th class="px-6 py-4">Nama Paket</th>
                                <th class="px-6 py-4">Deskripsi</th>
                                <th class="px-6 py-4">Membership</th>
                                <th class="px-6 py-4">Sesi PT</th>
                                <th class="px-6 py-4">Harga Bundle</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="item in bundle_packages" :key="item.id" class="hover:bg-muted/20 transition-colors">
                                <td class="px-6 py-4 font-medium">{{ item.gym?.name }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center">
                                            <Package2 :size="14" class="text-primary" />
                                        </div>
                                        <span class="font-medium">{{ item.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-muted-foreground">{{ item.description || '-' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium">
                                        {{ formatDays(item.membership_duration_in_days) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 text-xs font-medium">
                                        {{ item.pt_sessions }} Sesi
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-green-700">
                                    {{ formatCurrency(item.price) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center gap-3">
                                        <Link :href="`/master/bundle-package/${item.id}/edit`"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-yellow-100 text-yellow-600 hover:bg-yellow-500 hover:text-white transition"
                                            title="Edit Paket Bundle">
                                            <Pencil :size="16" />
                                        </Link>
                                        <button @click="deleteBundlePackage(item.id)" type="button"
                                            class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition"
                                            title="Hapus Paket Bundle">
                                            <Trash2 :size="16" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="bundle_packages.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3 text-muted-foreground">
                                        <Package2 :size="40" class="opacity-30" />
                                        <p class="text-sm">Belum ada paket bundling. Klik <strong>Tambah Paket Bundling</strong> untuk memulai.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
