<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Pencil, Trash2 } from 'lucide-vue-next';

defineProps<{
    memberships: any[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Membership', href: '/master/membership' },
];

const deleteMembership = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus paket membership ini?')) {
        router.delete(`/master/membership/${id}`);
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

        <Head title="Master Membership" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-6">

                <div class="flex items-center justify-between">
                    <Heading title="Daftar Membership" description="Kelola paket membership gym dan promo aktif." />
                    <Link href="/master/membership/create"
                        class="rounded-xl bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90">
                        Tambah Membership
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
                            <tr v-for="item in memberships" :key="item.id" class="hover:bg-muted/20 transition-colors">
                                <td class="px-6 py-4 font-medium">{{ item.gym?.name }}</td>
                                <td class="px-6 py-4">{{ item.name }}</td>
                                <td class="px-6 py-4">{{ item.duration_in_days }} Hari</td>
                                <td class="px-6 py-4">{{ formatCurrency(item.price) }}</td>
                                <td class="px-6 py-4">
                                    <div v-if="item.membership_promos && item.membership_promos.length > 0"
                                        class="flex flex-col gap-2">
                                        <div v-for="promo in item.membership_promos" :key="promo.id"
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
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center gap-3">

                                        <Link :href="`/master/membership/${item.id}/edit`"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-yellow-100 text-yellow-600 hover:bg-yellow-500 hover:text-white transition"
                                            title="Edit Membership">
                                            <Pencil :size="16" />
                                        </Link>

                                        <button @click="deleteMembership(item.id)" type="button"
                                            class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition"
                                            title="Hapus Membership">
                                            <Trash2 :size="16" />
                                        </button>

                                    </div>
                                </td>
                            </tr>
                            <tr v-if="memberships.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-muted-foreground">
                                    Belum ada data membership.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AppLayout>
</template>