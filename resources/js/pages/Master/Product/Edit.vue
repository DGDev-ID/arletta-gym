<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { formatRupiah } from '@/helpers/formatRupiah';
import { Trash2, RotateCcw, Minus } from 'lucide-vue-next';

const props = defineProps<{
    product: any;
    gyms: { id: number; name: string }[];
    categories: { id: number; name: string }[];
    stockLogs: any[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Product', href: '/master/product' },
    { title: 'Edit', href: `/master/product/${props.product.id}/edit` },
];

const form = useForm({
    name: props.product.name,
    product_category_id: props.product.product_category_id,
    buy_price: props.product.buy_price,
    sell_price: props.product.sell_price,
});

const stockForm = useForm({ quantity: '' });
const reduceForm = useForm({ quantity: '' });

const submit = () => {
    form.put(`/master/product/${props.product.id}`);
};

const addStock = () => {
    stockForm.post(`/master/product/${props.product.id}/add-stock`);
};

const reduceStock = () => {
    if (!confirm(`Kurangi stok sebanyak ${reduceForm.quantity}?`)) return;
    reduceForm.post(`/master/product/${props.product.id}/reduce-stock`);
};

const resetStock = () => {
    if (!confirm('Reset stok produk ini menjadi 0?')) return;
    router.post(`/master/product/${props.product.id}/reset-stock`);
};

const deleteLog = (logId: number) => {
    if (!confirm('Hapus log ini dan kurangi stok produk?')) return;
    router.delete(`/master/product/stock-log/${logId}`);
};

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    // Konversi ke WIB (UTC+7)
    const wib = new Date(date.getTime() + 7 * 60 * 60 * 1000);
    const dd = String(wib.getUTCDate()).padStart(2, '0');
    const mm = String(wib.getUTCMonth() + 1).padStart(2, '0');
    const yyyy = wib.getUTCFullYear();
    const hh = String(wib.getUTCHours()).padStart(2, '0');
    const min = String(wib.getUTCMinutes()).padStart(2, '0');
    return `${dd}-${mm}-${yyyy} ${hh}:${min} WIB`;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Edit Product" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">
                    <Heading variant="small" title="Edit Product" description="Perbarui informasi produk." />

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Pilih Gym</label>
                            <select v-model="form.gym_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background">
                                <option v-for="gym in props.gyms" :key="gym.id" :value="gym.id">{{ gym.name }}</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Kategori Produk</label>
                            <select v-model="form.product_category_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Nama Produk</label>
                            <Input v-model="form.name" />
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Buy Price (IDR)</label>
                                <Input v-model="form.buy_price" type="number" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Sell Price (IDR)</label>
                                <Input v-model="form.sell_price" type="number" />
                            </div>
                        </div>

                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <button type="submit" :disabled="form.processing"
                                class="cursor-pointer rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90 disabled:opacity-50">
                                Update Product
                            </button>
                        </div>
                    </form>
                </div>

                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-6">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold">Tambah / Kurangi Stok</h3>
                        <div class="flex items-center gap-2 rounded-lg bg-muted px-4 py-2">
                            <span class="text-sm text-muted-foreground">Stok Saat Ini:</span>
                            <span class="text-lg font-bold text-primary">{{ props.product.stock ?? 0 }}</span>
                            <span class="text-sm text-muted-foreground">unit</span>
                        </div>
                    </div>

                    <!-- Tambah Stok -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-muted-foreground">Tambah Stok</label>
                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <Input v-model="stockForm.quantity" type="number" min="1" placeholder="Jumlah yang ditambah" />
                            </div>
                            <button @click.prevent="addStock" :disabled="stockForm.processing"
                                class="rounded-xl bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:opacity-50 whitespace-nowrap">
                                + Tambah Stok
                            </button>
                        </div>
                    </div>

                    <!-- Kurangi Stok -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-muted-foreground">Kurangi Stok</label>
                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <Input v-model="reduceForm.quantity" type="number" min="1" placeholder="Jumlah yang dikurangi" />
                            </div>
                            <button @click.prevent="reduceStock" :disabled="reduceForm.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-orange-500 px-5 py-2 text-sm font-medium text-white hover:bg-orange-600 disabled:opacity-50 whitespace-nowrap">
                                <Minus :size="14" /> Kurangi Stok
                            </button>
                        </div>
                    </div>

                    <!-- Reset Stok -->
                    <div class="flex justify-end pt-2 border-t">
                        <button @click.prevent="resetStock" type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-100 transition">
                            <RotateCcw :size="14" /> Reset Stok ke 0
                        </button>
                    </div>
                </div>

                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-4">
                    <h3 class="font-semibold">Log Barang Masuk</h3>
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr class="text-muted-foreground">
                                <th class="px-6 py-3 text-left">Waktu</th>
                                <th class="px-6 py-3 text-right">Quantity</th>
                                <th class="px-6 py-3 text-right">Buy</th>
                                <th class="px-6 py-3 text-right">Sell</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in props.stockLogs" :key="log.id" class="border-t hover:bg-muted/40">
                                <td class="px-6 py-3">{{ formatDate(log.created_at) }}</td>
                                <td class="px-6 py-3 text-right">{{ log.quantity }}</td>
                                <td class="px-6 py-3 text-right">{{ formatRupiah(log.buy_price) }}</td>
                                <td class="px-6 py-3 text-right">{{ formatRupiah(log.sell_price) }}</td>
                                <td class="px-6 py-3 text-right">
                                    <button @click="deleteLog(log.id)" type="button"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition"
                                        title="Hapus Log">
                                        <Trash2 :size="14" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="props.stockLogs.length === 0">
                                <td colspan="5" class="px-6 py-6 text-center text-muted-foreground">Belum ada log barang masuk.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
