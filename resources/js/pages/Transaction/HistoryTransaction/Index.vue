<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Eye } from 'lucide-vue-next';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'History Transaction', href: '/transaction/history' },
];

const props = defineProps<{ transactions: any }>();

import { ref } from 'vue';

const formattedTransactions = computed(() => {
    // Laravel pagination: data ada di props.transactions.data
    return props.transactions.data
        .filter((trx: { status: string; }) => trx.status === 'success')
        .map((trx: { unique_id: any; id: any; user: { name: any; }; total_price: any; price: any; created_at: string | number | Date; status: any; method_midtrans_detail: any; }, idx: number) => ({
            no: (props.transactions.current_page - 1) * props.transactions.per_page + idx + 1,
            id: trx.unique_id || trx.id,
            member: trx.user?.name || '-',
            amount: trx.total_price || trx.price || 0,
            date: trx.created_at ? new Date(trx.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-',
            status: trx.status,
            method: trx.method_midtrans_detail || 'manual',
            raw: trx,
        }));
});
import { router } from '@inertiajs/vue3';
import { formatRupiah } from '@/helpers/formatRupiah';
function setStatus(status: 'success' | 'failed') {
    if (!selectedTransaction.value) return;
    router.patch(`/transaction/history/${selectedTransaction.value.id}`, {
        status
    }, {
        preserveState: true,
        onSuccess: () => {
            closeModal();
        }
    });
}

const showModal = ref(false);
const selectedTransaction = ref<any>(null);

function openModal(trx: any) {
    selectedTransaction.value = trx.raw;
    showModal.value = true;
}
function closeModal() {
    showModal.value = false;
    selectedTransaction.value = null;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="History Transaction" />
        <h1 class="sr-only">History Transaction</h1>

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">
                <div class="rounded-2xl border bg-background shadow-sm overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr class="text-muted-foreground">
                                <th class="px-6 py-4 text-left font-medium">No</th>
                                <th class="px-6 py-4 text-left font-medium">Member</th>
                                <th class="px-6 py-4 text-left font-medium">Jumlah</th>
                                <th class="px-6 py-4 text-left font-medium">Tanggal</th>
                                <th class="px-6 py-4 text-left font-medium">Status</th>
                                <th class="px-6 py-4 text-left font-medium">Metode</th>
                                <th class="px-6 py-4 text-left font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="trx in formattedTransactions" :key="trx.id" class="border-t hover:bg-muted/40 transition">
                                <td class="px-6 py-4">{{ trx.no }}</td>
                                <td class="px-6 py-4 font-medium">{{ trx.member }}</td>
                                <td class="px-6 py-4 font-semibold"> {{ formatRupiah(trx.amount) }}</td>
                                <td class="px-6 py-4">{{ trx.date }}</td>
                                <td class="px-6 py-4">
                                    <span v-if="trx.status === 'success'" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400 ring-1 ring-inset ring-emerald-200 dark:ring-emerald-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Paid
                                    </span>
                                    <span v-else-if="trx.status === 'pending'" class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 dark:bg-amber-900/30 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:text-amber-400 ring-1 ring-inset ring-amber-200 dark:ring-amber-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                        pending
                                    </span>
                                    <span v-else-if="trx.status === 'failed'" class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 dark:bg-rose-900/30 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:text-rose-400 ring-1 ring-inset ring-rose-200 dark:ring-rose-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        failed
                                    </span>
                                </td>
                                <td class="px-6 py-4">{{ trx.method }}</td>
                                <td class="px-6 py-4">
                                    <button @click="router.visit(`/transaction/history/${trx.raw.id}`)" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-primary text-white hover:bg-primary-dark transition cursor-pointer" title="Lihat Detail">
                                        <Eye :size="18" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                                <!-- Pagination -->
                                <div class="flex justify-end mt-4 mb-6">
                                    <nav v-if="props.transactions.links && props.transactions.links.length > 1" class="inline-flex rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                                        <template v-for="link in props.transactions.links">
                                            <button
                                                v-if="link.url"
                                                :key="link.label"
                                                @click="router.visit(link.url)"
                                                :class="['px-3 py-2 text-sm font-medium', link.active ? 'bg-primary text-white' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800', 'rounded-lg']"
                                            >{{ link.label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»') }}</button>
                                            <span v-else :key="link.label" class="px-3 py-2 text-sm text-zinc-400">{{ link.label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»') }}</span>
                                        </template>
                                    </nav>
                                </div>
                    <!-- Modal Detail Transaksi dihapus, sekarang pindah route ke detail -->
                    </div>
            </div>
        </div>
    </AppLayout>
</template>
