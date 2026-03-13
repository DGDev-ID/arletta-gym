<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

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

const submit = () => {
    form.put(`/master/product/${props.product.id}`);
};

const addStock = () => {
    stockForm.post(`/master/product/${props.product.id}/add-stock`);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Edit Product" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-3xl mx-auto px-6 space-y-8">

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

                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-4">
                    <h3 class="font-semibold">Tambah Stok</h3>
                    <div class="grid md:grid-cols-3 gap-4 items-end">
                        <div>
                            <label class="text-sm font-medium">Quantity</label>
                            <Input v-model="stockForm.quantity" type="number" />
                        </div>
                        <div class="md:col-span-2 flex justify-end">
                            <button @click.prevent="addStock" :disabled="stockForm.processing"
                                class="rounded-xl bg-primary px-4 py-2 text-sm text-primary-foreground">Tambah Stok</button>
                        </div>
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
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in props.stockLogs" :key="log.id" class="border-t hover:bg-muted/40">
                                <td class="px-6 py-3">{{ log.created_at }}</td>
                                <td class="px-6 py-3 text-right">{{ log.quantity }}</td>
                                <td class="px-6 py-3 text-right">{{ log.buy_price }}</td>
                                <td class="px-6 py-3 text-right">{{ log.sell_price }}</td>
                            </tr>
                            <tr v-if="props.stockLogs.length === 0">
                                <td colspan="4" class="px-6 py-6 text-center text-muted-foreground">Belum ada log barang masuk.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
