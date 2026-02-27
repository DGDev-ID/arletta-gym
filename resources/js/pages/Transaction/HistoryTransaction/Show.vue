<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatRupiah } from '@/helpers/formatRupiah';

const props = defineProps<{ transaction: any }>();

const trx = computed(() => props.transaction);
</script>

<template>
    <AppLayout>
        <Head :title="trx ? ('Detail Transaction #' + (trx.unique_id || trx.id)) : 'Detail Transaction'" />
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6">
                <div v-if="trx" class="rounded-2xl border bg-background shadow-sm p-8">
                    <div class="flex items-center gap-3 mb-6">
                        
                        <div>
                            <h2 class="text-lg font-semibold">Detail Transaksi</h2>
                            <div class="text-xs text-muted-foreground">ID: {{ trx.unique_id || trx.id }}</div>
                        </div>
                        <span :class="[
                            'ml-auto px-3 py-1 rounded-full text-xs font-bold',
                            trx.status === 'success' ? 'bg-emerald-50 text-emerald-700' : '',
                            trx.status === 'pending' ? 'bg-amber-50 text-amber-700' : '',
                            trx.status === 'failed' ? 'bg-rose-50 text-rose-700' : ''
                        ]">
                            {{ trx.status === 'success' ? 'Paid' : trx.status === 'pending' ? 'Pending' : 'Failed' }}
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <div><span class="text-muted-foreground text-xs">Member</span><div class="font-medium">{{ trx.user?.name || '-' }}</div></div>
                            <div><span class="text-muted-foreground text-xs">Paket</span><div class="font-medium">{{ trx.membership?.name || trx.fullPt?.name || trx.installmentPt?.name || '-' }}</div></div>
                            <div><span class="text-muted-foreground text-xs">Metode</span><div class="font-medium">{{ trx.method_midtrans_detail || 'manual' }}</div></div>
                            <div><span class="text-muted-foreground text-xs">Tipe Transaksi</span><div class="font-medium">{{ trx.transaction_type || '-' }}</div></div>
                            <div><span class="text-muted-foreground text-xs">Tanggal</span><div class="font-medium">{{ trx.created_at ? new Date(trx.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-' }}</div></div>
                            <div><span class="text-muted-foreground text-xs">Deskripsi</span><div class="font-medium">{{ trx.description || '-' }}</div></div>
                        </div>
                        <div class="space-y-3">
                            <div><span class="text-muted-foreground text-xs">Metode</span><div class="font-medium">{{ trx.method || 'manual' }}</div></div>
                            <div><span class="text-muted-foreground text-xs">Harga</span><div class="font-semibold text-lg">{{ (formatRupiah(trx.price) || 0) }}</div></div>
                            <div><span class="text-muted-foreground text-xs">Biaya Midtrans</span><div class="font-semibold text-lg">{{ (formatRupiah(trx.midtrans_fee) || 0) }}</div></div>
                            <div><span class="text-muted-foreground text-xs">Biaya PPN</span><div class="font-semibold text-lg">{{ (formatRupiah(trx.ppn_fee) || 0) }}</div></div>
                            <div><span class="text-muted-foreground text-xs">Total Biaya</span><div class="font-semibold text-lg">{{ (formatRupiah(trx.total_price) || 0) }}</div></div>
                            <div><span class="text-muted-foreground text-xs">Sesi / Hari</span><div class="font-medium">{{ trx.sessions_or_days || '-' }}</div></div>
                        </div>
                    </div>
                </div>
                <div v-else class="rounded-2xl border bg-background shadow-sm p-8 text-center text-muted-foreground">
                    <h2 class="text-lg font-semibold mb-6">Detail Transaksi</h2>
                    <p>Data transaksi tidak ditemukan.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
