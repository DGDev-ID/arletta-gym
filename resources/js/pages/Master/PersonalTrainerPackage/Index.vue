<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    pt_packages: any[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Personal Trainer Package', href: '/master/personal-trainer-package' },
];

const deletePersonalTrainerPackage = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus paket personal trainer ini?')) {
        router.delete(`/master/personal-trainer-package/${id}`);
    }
};

// Helper untuk format mata uang
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Master Personal Trainer Package" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-6">

                <div class="flex items-center justify-between">
                    <Heading title="Daftar Personal Trainer Package" description="Kelola paket personal trainer gym dan promo aktif." />
                    <Link href="/master/personal-trainer-package/create"
                        class="rounded-xl bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90">
                        Tambah Personal Trainer Package
                    </Link>
                </div>

                <div class="rounded-xl border bg-background overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/50 text-muted-foreground font-medium border-b">
                            <tr>
                                <th class="px-6 py-4">Gym</th>
                                <th class="px-6 py-4">Nama Paket</th>
                                <th class="px-6 py-4">Durasi</th>
                                <th class="px-6 py-4">Harga</th>
                                <th class="px-6 py-4">Promo Aktif</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="item in pt_packages" :key="item.id" class="hover:bg-muted/20 transition-colors">
                                <td class="px-6 py-4 font-medium">{{ item.gym?.name }}</td>
                                <td class="px-6 py-4">{{ item.name }}</td>
                                <td class="px-6 py-4">{{ item.duration_in_sessions }} Sesi</td>
                                <td class="px-6 py-4">{{ formatCurrency(item.price) }}</td>
                                <td class="px-6 py-4">
                                    <div v-if="item.pt_package_promos && item.pt_package_promos.length > 0"
                                        class="flex flex-col gap-2">
                                        <div v-for="promo in item.pt_package_promos" :key="promo.id"
                                            class="flex flex-col border-l-2 border-green-500 pl-2 py-0.5">

                                            <span class="text-xs font-bold text-green-600">
                                                {{ promo.unique_code }}
                                            </span>

                                            <span class="text-[10px] text-muted-foreground uppercase">
                                                {{ promo.type.replace('_', ' ') }}: {{ promo.value }}
                                            </span>
                                        </div>
                                    </div>

                                    <span v-else class="text-muted-foreground text-xs italic">-</span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <Link :href="`/master/personal-trainer-package/${item.id}/edit`"
                                        class="text-primary hover:underline text-sm font-medium">
                                        Edit
                                    </Link>
                                    <button @click="deletePersonalTrainerPackage(item.id)" class="text-destructive hover:underline">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="pt_packages.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-muted-foreground">
                                    Belum ada data personal trainer package.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AppLayout>
</template>