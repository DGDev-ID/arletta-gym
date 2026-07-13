<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Eye, Filter, Download, Printer } from 'lucide-vue-next';
import { formatRupiah } from '@/helpers/formatRupiah';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'History Transaction', href: '/transaction/history' },
];

const props = defineProps<{
    transactions: any;
    gyms: { id: number; name: string }[];
    filters: {
        gyms: (string | number)[];
        methods: string[];
        transaction_types: string[];
        date_start: string;
        date_end: string;
    };
}>();

const methodOptions = [
    { value: 'manual', label: 'Manual' },
    { value: 'midtrans', label: 'Midtrans' },
];

const transactionTypeOptions = [
    { value: 'membership', label: 'Membership' },
    { value: 'full_pt', label: 'Full PT' },
    { value: 'installment_pt', label: 'Instalment PT' },
];

const selectedGyms = ref<(string | number)[]>(props.filters.gyms);
const selectedMethods = ref<string[]>(props.filters.methods);
const selectedTransactionTypes = ref<string[]>(props.filters.transaction_types);
const selectedDateStart = ref<string>(props.filters.date_start);
const selectedDateEnd = ref<string>(props.filters.date_end);

const isAllGyms = computed(() => selectedGyms.value.includes('all'));
const isAllMethods = computed(() => selectedMethods.value.includes('all'));
const isAllTypes = computed(() => selectedTransactionTypes.value.includes('all'));

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

function toggleMethod(method: string) {
    if (method === 'all') {
        selectedMethods.value = ['all'];
        return;
    }
    let vals = selectedMethods.value.filter(v => v !== 'all');
    if (vals.includes(method)) {
        vals = vals.filter(v => v !== method);
    } else {
        vals.push(method);
    }
    selectedMethods.value = vals.length === 0 ? ['all'] : vals;
}

function toggleType(type: string) {
    if (type === 'all') {
        selectedTransactionTypes.value = ['all'];
        return;
    }
    let vals = selectedTransactionTypes.value.filter(v => v !== 'all');
    if (vals.includes(type)) {
        vals = vals.filter(v => v !== type);
    } else {
        vals.push(type);
    }
    selectedTransactionTypes.value = vals.length === 0 ? ['all'] : vals;
}

function applyFilters() {
    router.get('/transaction/history', {
        gyms: selectedGyms.value,
        methods: selectedMethods.value,
        transaction_types: selectedTransactionTypes.value,
        date_start: selectedDateStart.value,
        date_end: selectedDateEnd.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function buildExportUrl() {
    const params = new URLSearchParams();
    selectedGyms.value.forEach(v => params.append('gyms[]', String(v)));
    selectedMethods.value.forEach(v => params.append('methods[]', String(v)));
    selectedTransactionTypes.value.forEach(v => params.append('transaction_types[]', String(v)));
    if (selectedDateStart.value) params.append('date_start', selectedDateStart.value);
    if (selectedDateEnd.value) params.append('date_end', selectedDateEnd.value);
    return '/transaction/history-export-csv?' + params.toString();
}

const printingId = ref<number | null>(null);

function getXsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function printInvoice(trxRaw: any) {
    printingId.value = trxRaw.id;
    try {
        const response = await fetch(`/transaction/history/${trxRaw.id}/print-invoice`, {
            method: 'POST',
            headers: {
                'X-XSRF-TOKEN': getXsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        });
        if (!response.ok) {
            alert('Gagal mengirim print job ke printer.');
        }
    } catch {
        alert('Gagal terhubung ke printer server.');
    } finally {
        printingId.value = null;
    }
}

function downloadInvoice(trxRaw: any) {
    window.open(`/transaction/history/${trxRaw.id}/download-invoice`, '_blank');
}

const formattedTransactions = computed(() => {
    return props.transactions.data
        .filter((trx: any) => trx.status === 'success')
        .map((trx: any, idx: number) => ({
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

const showFilter = ref(false);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="History Transaction" />
        <h1 class="sr-only">History Transaction</h1>

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <!-- Header + Actions -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h2 class="text-lg font-semibold">History Transaction</h2>
                    <div class="flex items-center gap-3">
                        <button @click="showFilter = !showFilter"
                            class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-medium hover:bg-muted transition-colors cursor-pointer">
                            <Filter :size="16" />
                            Filter
                        </button>
                        <a :href="buildExportUrl()"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow hover:opacity-90 transition-opacity">
                            <Download :size="16" />
                            Export Transaction
                        </a>
                    </div>
                </div>

                <!-- Filter Section -->
                <div v-if="showFilter" class="rounded-2xl border bg-background p-6 shadow-sm space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-6">
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

                        <!-- Method Filter -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Metode Pembayaran</h4>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" :checked="isAllMethods" @change="toggleMethod('all')"
                                        class="rounded border-gray-300 text-primary focus:ring-primary" />
                                    <span class="text-sm font-medium">Semua Metode</span>
                                </label>
                                <label v-for="m in methodOptions" :key="m.value" class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                        :checked="!isAllMethods && selectedMethods.includes(m.value)"
                                        @change="toggleMethod(m.value)"
                                        class="rounded border-gray-300 text-primary focus:ring-primary" />
                                    <span class="text-sm">{{ m.label }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Transaction Type Filter -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Transaction Type</h4>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" :checked="isAllTypes" @change="toggleType('all')"
                                        class="rounded border-gray-300 text-primary focus:ring-primary" />
                                    <span class="text-sm font-medium">Semua Tipe</span>
                                </label>
                                <label v-for="t in transactionTypeOptions" :key="t.value" class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                        :checked="!isAllTypes && selectedTransactionTypes.includes(t.value)"
                                        @change="toggleType(t.value)"
                                        class="rounded border-gray-300 text-primary focus:ring-primary" />
                                    <span class="text-sm">{{ t.label }}</span>
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

                <!-- Table -->
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
                                    <div class="flex items-center gap-2">
                                        <button @click="router.visit(`/transaction/history/${trx.raw.id}`)" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-primary text-white hover:bg-primary-dark transition cursor-pointer" title="Lihat Detail">
                                            <Eye :size="18" />
                                        </button>
                                        <button @click="printInvoice(trx.raw)" :disabled="printingId === trx.raw.id" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-amber-500 text-white hover:bg-amber-600 transition cursor-pointer disabled:opacity-50" title="Print Invoice">
                                            <Printer :size="18" />
                                        </button>
                                        <button @click="downloadInvoice(trx.raw)" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-emerald-600 text-white hover:bg-emerald-700 transition cursor-pointer" title="Download Invoice">
                                            <Download :size="18" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="formattedTransactions.length === 0">
                                <td colspan="7" class="px-6 py-10 text-center text-muted-foreground">Tidak ada transaksi ditemukan.</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-4 mb-6">
                        <Pagination :links="props.transactions.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
