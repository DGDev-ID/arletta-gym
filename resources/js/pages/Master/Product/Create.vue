<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const props = defineProps<{
    gyms: { id: number; name: string }[];
    categories: { id: number; name: string }[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Product', href: '/master/product' },
    { title: 'Create', href: '/master/product/create' },
];

const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'bottom' },
    ripple: true,
    dismissible: true,
});

const form = useForm({
    gym_id: '',
    product_category_id: '',
    name: '',
    buy_price: '',
    sell_price: '',
});

const submit = () => {
    form.post('/master/product', {
        onError: (errors) => {
            if (errors.name) {
                notyf.error(errors.name);
            } else {
                notyf.error('Gagal menyimpan produk. Periksa kembali form Anda.');
            }
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Tambah Product" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-2xl mx-auto px-6">
                <div class="rounded-7xl border bg-background shadow-sm p-8 space-y-8">

                    <Heading variant="small" title="Tambah Product Baru" description="Tambahkan produk untuk Produk." />

                    <form @submit.prevent="submit" class="space-y-6">

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Pilih Gym</label>
                            <select v-model="form.gym_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background">
                                <option value="" disabled>Pilih lokasi gym</option>
                                <option v-for="gym in props.gyms" :key="gym.id" :value="gym.id">{{ gym.name }}</option>
                            </select>
                            <p v-if="form.errors.gym_id" class="text-xs text-destructive">{{ form.errors.gym_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Kategori Produk</label>
                            <select v-model="form.product_category_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="" disabled>Pilih kategori</option>
                                <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <p v-if="form.errors.product_category_id" class="text-xs text-destructive">{{ form.errors.product_category_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Nama Produk</label>
                            <Input v-model="form.name" placeholder="Contoh: Protein Bar" />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
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
                                Simpan Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
