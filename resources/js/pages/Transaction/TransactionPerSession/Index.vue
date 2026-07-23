<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { formatRupiah } from '@/helpers/formatRupiah';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import Pagination from '@/components/Pagination.vue';
import { Filter, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    gyms: { id: number; name: string; price_per_session?: number }[];
    transactions: any;
    isSuperAdmin: boolean;
    filters: {
        gyms: (string | number)[];
        statuses: string[];
        date_start: string;
        date_end: string;
    };
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Transaction Per Session', href: '/transaction/transaction-per-session' },
];

const showFilter = ref(false);

const selectedGyms = ref<(string | number)[]>(props.filters.gyms);
const selectedStatuses = ref<string[]>(props.filters.statuses);
const selectedDateStart = ref<string>(props.filters.date_start);
const selectedDateEnd = ref<string>(props.filters.date_end);

const isAllGyms = computed(() => selectedGyms.value.includes('all'));
const isAllStatuses = computed(() => selectedStatuses.value.includes('all'));

const statusOptions = [
    { value: 'pending', label: 'Pending' },
    { value: 'success', label: 'Success' },
    { value: 'failed', label: 'Failed' },
];

function toggleGym(gymId: number | string) {
    if (gymId === 'all') {
        selectedGyms.value = ['all'];
        return;
    }
    let vals = selectedGyms.value.filter(v => v !== 'all');
    if (vals.includes(gymId)) {
        vals = vals.filter(v => v !== gymId);
    } else {
        vals.push(gymId);
    }
    selectedGyms.value = vals.length === 0 ? ['all'] : vals;
}

function toggleStatus(status: string) {
    if (status === 'all') {
        selectedStatuses.value = ['all'];
        return;
    }
    let vals = selectedStatuses.value.filter(v => v !== 'all');
    if (vals.includes(status)) {
        vals = vals.filter(v => v !== status);
    } else {
        vals.push(status);
    }
    selectedStatuses.value = vals.length === 0 ? ['all'] : vals;
}

function applyFilters() {
    router.get('/transaction/transaction-per-session', {
        gyms: selectedGyms.value,
        statuses: selectedStatuses.value,
        date_start: selectedDateStart.value,
        date_end: selectedDateEnd.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

const formattedTransactions = computed(() => {
    return props.transactions.data.map((trx: any, idx: number) => ({
        no: (props.transactions.current_page - 1) * props.transactions.per_page + idx + 1,
        id: trx.id,
        name: trx.name,
        phone: trx.phone_number,
        gym: trx.gym?.name || '-',
        price: trx.price || 0,
        payment_method: trx.payment_method || 'cash',
        status: trx.status,
        date: new Date(trx.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }),
        raw: trx,
    }));
});

function goToCreate() {
    router.visit('/transaction/transaction-per-session/create');
}

const notyf = new Notyf({ duration: 3000, position: { x: 'right', y: 'bottom' } });
const processingId = ref<number | null>(null);
const deletingId = ref<number | null>(null);

function setStatus(id: number, status: 'success' | 'failed') {
    if (!confirm('Yakin ingin mengubah status?')) return;
    processingId.value = id;

    router.patch(`/transaction/transaction-per-session/${id}`, { status }, {
        preserveState: true,
        onSuccess: () => {
            notyf.success('Status transaksi berhasil diperbarui.');
        },
        onError: () => {
            notyf.error('Gagal mengubah status transaksi.');
        },
        onFinish: () => {
            processingId.value = null;
        }
    });
}

function deleteTransaction(id: number) {
    if (!confirm('Yakin ingin menghapus transaksi ini? Tindakan ini tidak bisa dibatalkan.')) return;
    deletingId.value = id;

    router.delete(`/transaction/transaction-per-session/${id}`, {
        preserveState: false,
        onSuccess: () => {
            notyf.success('Transaksi berhasil dihapus.');
        },
        onError: () => {
            notyf.error('Gagal menghapus transaksi.');
        },
        onFinish: () => {
            deletingId.value = null;
        }
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Transaction Per Session" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-6">
                <!-- Header + Actions -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h2 class="text-lg font-semibold">Transaction Per Session</h2>
                    <div class="flex items-center gap-3">
                        <button @click="showFilter = !showFilter"
                            class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors cursor-pointer bg-background">
                            <Filter :size="16" />
                            Filter
                        </button>
                        <Button @click="goToCreate" class="rounded-xl">Tambah Transaction</Button>
                    </div>
                </div>

                <!-- Filter Section -->
                <div v-if="showFilter" class="rounded-2xl border bg-background p-6 shadow-sm space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                        <!-- Gym Filter -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Gym</h4>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" :checked="isAllGyms" @change="toggleGym('all')"
                                        class="rounded border-gray-300 text-primary focus:ring-primary" />
                                    <span class="text-sm font-medium">Semua Gym</span>
                                </label>
                                <label v-for="gym in props.gyms" :key="gym.id" class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                        :checked="!isAllGyms && selectedGyms.includes(gym.id)"
                                        @change="toggleGym(gym.id)"
                                        class="rounded border-gray-300 text-primary focus:ring-primary" />
                                    <span class="text-sm">{{ gym.name }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Status Transaksi</h4>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" :checked="isAllStatuses" @change="toggleStatus('all')"
                                        class="rounded border-gray-300 text-primary focus:ring-primary" />
                                    <span class="text-sm font-medium">Semua Status</span>
                                </label>
                                <label v-for="m in statusOptions" :key="m.value" class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                        :checked="!isAllStatuses && selectedStatuses.includes(m.value)"
                                        @change="toggleStatus(m.value)"
                                        class="rounded border-gray-300 text-primary focus:ring-primary" />
                                    <span class="text-sm">{{ m.label }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Date Start Filter -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Dari Tanggal</h4>
                            <input type="date" v-model="selectedDateStart"
                                class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" />
                        </div>

                        <!-- Date End Filter -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Sampai Tanggal</h4>
                            <input type="date" v-model="selectedDateEnd"
                                class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button @click="applyFilters"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow hover:opacity-90 transition-opacity cursor-pointer">
                            Terapkan Filter
                        </button>
                    </div>
                </div>

                <!-- Unified Table -->
                <div class="rounded-2xl border bg-background shadow-sm overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr class="text-muted-foreground">
                                <th class="px-6 py-4 text-left font-medium">No</th>
                                <th class="px-6 py-4 text-left font-medium">Nama</th>
                                <th class="px-6 py-4 text-left font-medium">No HP</th>
                                <th class="px-6 py-4 text-left font-medium">Gym</th>
                                <th class="px-6 py-4 text-left font-medium">Harga</th>
                                <th class="px-6 py-4 text-left font-medium">Metode</th>
                                <th class="px-6 py-4 text-left font-medium">Tanggal</th>
                                <th class="px-6 py-4 text-left font-medium">Status</th>
                                <th class="px-6 py-4 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="trx in formattedTransactions" :key="trx.id" class="border-t hover:bg-muted/40 transition">
                                <td class="px-6 py-4">{{ trx.no }}</td>
                                <td class="px-6 py-4 font-medium">{{ trx.name }}</td>
                                <td class="px-6 py-4">{{ trx.phone }}</td>
                                <td class="px-6 py-4">{{ trx.gym }}</td>
                                <td class="px-6 py-4 font-semibold">{{ formatRupiah(trx.price) }}</td>
                                <td class="px-6 py-4">
                                    <span :class="trx.payment_method === 'debit'
                                        ? 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-800'
                                        : 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:ring-emerald-800'"
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset capitalize">
                                        {{ trx.payment_method }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">{{ trx.date }}</td>
                                <td class="px-6 py-4">
                                    <span v-if="trx.status === 'success'" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400 ring-1 ring-inset ring-emerald-200 dark:ring-emerald-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Success
                                    </span>
                                    <span v-else-if="trx.status === 'pending'" class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 dark:bg-amber-900/30 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:text-amber-400 ring-1 ring-inset ring-amber-200 dark:ring-amber-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                        Pending
                                    </span>
                                    <span v-else-if="trx.status === 'failed'" class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 dark:bg-rose-900/30 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:text-rose-400 ring-1 ring-inset ring-rose-200 dark:ring-rose-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        Failed
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <template v-if="trx.status === 'pending'">
                                            <button @click="setStatus(trx.id, 'success')" :disabled="processingId === trx.id" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium transition cursor-pointer disabled:opacity-50">
                                                Success
                                            </button>
                                            <button @click="setStatus(trx.id, 'failed')" :disabled="processingId === trx.id" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-medium transition cursor-pointer disabled:opacity-50">
                                                Failed
                                            </button>
                                        </template>
                                        <!-- Tombol hapus hanya untuk Super Admin -->
                                        <button
                                            v-if="props.isSuperAdmin"
                                            @click="deleteTransaction(trx.id)"
                                            :disabled="deletingId === trx.id"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-100 hover:bg-rose-200 text-rose-700 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 dark:text-rose-400 transition cursor-pointer disabled:opacity-50"
                                            title="Hapus Transaksi">
                                            <Trash2 :size="14" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="formattedTransactions.length === 0">
                                <td colspan="9" class="px-6 py-10 text-center text-muted-foreground">Tidak ada transaksi ditemukan.</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-4 mb-6 px-4">
                        <Pagination :links="props.transactions.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
