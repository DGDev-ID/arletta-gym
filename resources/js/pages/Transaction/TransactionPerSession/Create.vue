<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { formatRupiah } from '@/helpers/formatRupiah';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const props = defineProps<{
    gyms: { id: number; name: string; price_per_session?: number }[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Transaction Per Session', href: '/transaction/transaction-per-session' },
    { title: 'Create', href: '/transaction/transaction-per-session/create' },
];

const d = new Date();
const localDate = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

const form = useForm({
    gym_id: '',
    name: '',
    phone_number: '',
    transaction_date: localDate,
    payment_method: 'cash' as 'cash' | 'debit',
});

const notyf = new Notyf({
    duration: 3000,
    position: { x: 'right', y: 'bottom' },
});

const selectedGym = computed(() => props.gyms.find(g => String(g.id) === String(form.gym_id)));

function submit() {
    form.post('/transaction/transaction-per-session', {
        onSuccess: () => {
            notyf.success('Transaction per session berhasil dibuat.');
            router.visit('/transaction/transaction-per-session');
        },
        onError: (errors) => {
            notyf.error('Gagal menyimpan transaksi. Silakan cek input.');
        }
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Create Transaction Per Session" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Tambah Transaction Per Session</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm text-muted-foreground mb-1">Pilih Gym</label>
                                <select v-model="form.gym_id" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">-- Pilih Gym --</option>
                                    <option v-for="gym in props.gyms" :key="gym.id" :value="gym.id">{{ gym.name }}</option>
                                </select>
                                <div v-if="form.errors.gym_id" class="text-rose-600 text-sm mt-1">{{ form.errors.gym_id }}</div>
                            </div>

                            <div>
                                <label class="block text-sm text-muted-foreground mb-1">Price per session</label>
                                <input type="text" :value="selectedGym ? formatRupiah(selectedGym.price_per_session || 0) : '-'" disabled class="w-full rounded-lg border border-input bg-muted/20 px-3 py-2 text-sm" />
                            </div>

                            <div>
                                <label class="block text-sm text-muted-foreground mb-1">Nama</label>
                                <input v-model="form.name" type="text" class="w-full rounded-lg border border-input px-3 py-2 text-sm" />
                                <div v-if="form.errors.name" class="text-rose-600 text-sm mt-1">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="block text-sm text-muted-foreground mb-1">Tanggal Transaksi</label>
                                <input v-model="form.transaction_date" type="date" class="w-full rounded-lg border border-input px-3 py-2 text-sm" />
                                <div v-if="form.errors.transaction_date" class="text-rose-600 text-sm mt-1">{{ form.errors.transaction_date }}</div>
                            </div>

                            <div>
                                <label class="block text-sm text-muted-foreground mb-1">Nomor HP</label>
                                <input v-model="form.phone_number" type="text" class="w-full rounded-lg border border-input px-3 py-2 text-sm" />
                                <div v-if="form.errors.phone_number" class="text-rose-600 text-sm mt-1">{{ form.errors.phone_number }}</div>
                            </div>

                            <div>
                                <label class="block text-sm text-muted-foreground mb-2">Metode Pembayaran</label>
                                <div class="flex gap-3">
                                    <button
                                        type="button"
                                        @click="form.payment_method = 'cash'"
                                        :class="form.payment_method === 'cash'
                                            ? 'bg-primary text-white ring-2 ring-primary shadow-md'
                                            : 'bg-muted text-foreground hover:bg-muted/70'"
                                        class="flex-1 flex flex-col items-center gap-1 rounded-xl px-4 py-3 text-sm font-medium transition-all cursor-pointer">
                                        Cash
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.payment_method = 'debit'"
                                        :class="form.payment_method === 'debit'
                                            ? 'bg-primary text-white ring-2 ring-primary shadow-md'
                                            : 'bg-muted text-foreground hover:bg-muted/70'"
                                        class="flex-1 flex flex-col items-center gap-1 rounded-xl px-4 py-3 text-sm font-medium transition-all cursor-pointer">
                                        Debit
                                    </button>
                                </div>
                                <div v-if="form.errors.payment_method" class="text-rose-600 text-sm mt-1">{{ form.errors.payment_method }}</div>
                            </div>

                            <div class="flex justify-end">
                                <Button @click.prevent="submit">Submit</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
