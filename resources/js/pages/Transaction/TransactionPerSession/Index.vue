<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { formatRupiah } from '@/helpers/formatRupiah';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const props = defineProps<{
    gyms: { id: number; name: string; price_per_session?: number }[];
    successTransactions: any[];
    pendingTransactions: any[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Transaction Per Session', href: '/transaction/transaction-per-session' },
];

const successList = computed(() => props.successTransactions.map((t, idx) => ({
    no: idx + 1,
    id: t.id,
    name: t.name,
    phone: t.phone_number,
    gym: t.gym?.name || '-',
    price: t.price || 0,
    date: t.created_at,
    raw: t,
})));

const pendingList = computed(() => props.pendingTransactions.map((t, idx) => ({
    no: idx + 1,
    id: t.id,
    name: t.name,
    phone: t.phone_number,
    gym: t.gym?.name || '-',
    price: t.price || 0,
    date: t.created_at,
    raw: t,
})));

function goToCreate() {
    router.visit('/transaction/transaction-per-session/create');
}

const notyf = new Notyf({ duration: 3000, position: { x: 'right', y: 'bottom' } });
const processingId = ref<number | null>(null);

function setStatus(id: number, status: 'success' | 'failed') {
    if (!confirm('Yakin ingin mengubah status?')) return;
    processingId.value = id;

    router.patch(`/transaction/transaction-per-session/${id}`, { status }, {
        preserveState: true,
        onSuccess: () => {
            notyf.success('Status transaksi berhasil diperbarui.');
            router.reload();
        },
        onError: () => {
            notyf.error('Gagal mengubah status transaksi.');
        },
        onFinish: () => {
            processingId.value = null;
        }
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Transaction Per Session" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold">Transaction Per Session</h2>
                    <Button @click="goToCreate">Tambah Transaction Per Session</Button>
                </div>

                <div class="rounded-2xl border bg-background shadow-sm overflow-hidden">
                    <div class="p-4">
                        <h3 class="font-medium mb-2">Success Transactions</h3>
                        <table class="min-w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr class="text-muted-foreground">
                                    <th class="px-6 py-3 text-left">No</th>
                                    <th class="px-6 py-3 text-left">Nama</th>
                                    <th class="px-6 py-3 text-left">No HP</th>
                                    <th class="px-6 py-3 text-left">Gym</th>
                                    <th class="px-6 py-3 text-left">Harga</th>
                                    <th class="px-6 py-3 text-left">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="trx in successList" :key="trx.id" class="border-t hover:bg-muted/40 transition">
                                    <td class="px-6 py-3">{{ trx.no }}</td>
                                    <td class="px-6 py-3 font-medium">{{ trx.name }}</td>
                                    <td class="px-6 py-3">{{ trx.phone }}</td>
                                    <td class="px-6 py-3">{{ trx.gym }}</td>
                                    <td class="px-6 py-3 font-semibold">{{ formatRupiah(trx.price) }}</td>
                                    <td class="px-6 py-3">{{ new Date(trx.date).toLocaleString('id-ID') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl border bg-background shadow-sm overflow-hidden">
                    <div class="p-4">
                        <h3 class="font-medium mb-2">Pending Transactions</h3>
                        <table class="min-w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr class="text-muted-foreground">
                                    <th class="px-6 py-3 text-left">No</th>
                                    <th class="px-6 py-3 text-left">Nama</th>
                                    <th class="px-6 py-3 text-left">No HP</th>
                                    <th class="px-6 py-3 text-left">Gym</th>
                                    <th class="px-6 py-3 text-left">Harga</th>
                                    <th class="px-6 py-3 text-left">Tanggal</th>
                                    <th class="px-6 py-3 text-left">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="trx in pendingList" :key="trx.id" class="border-t hover:bg-muted/40 transition">
                                    <td class="px-6 py-3">{{ trx.no }}</td>
                                    <td class="px-6 py-3 font-medium">{{ trx.name }}</td>
                                    <td class="px-6 py-3">{{ trx.phone }}</td>
                                    <td class="px-6 py-3">{{ trx.gym }}</td>
                                    <td class="px-6 py-3 font-semibold">{{ formatRupiah(trx.price) }}</td>
                                    <td class="px-6 py-3">{{ new Date(trx.date).toLocaleString('id-ID') }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex gap-2">
                                            <button @click="setStatus(trx.id, 'success')" class="px-3 py-1 rounded bg-emerald-600 text-white text-sm">Success</button>
                                            <button @click="setStatus(trx.id, 'failed')" class="px-3 py-1 rounded bg-rose-600 text-white text-sm">Failed</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
