<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Pencil, Trash2, Search } from 'lucide-vue-next';
import { formatRupiah } from '@/helpers/formatRupiah';
import { ref, watch } from 'vue';

const breadcrumbItems = [
    { title: 'Master Products', href: '/master/product' }
];

const props = defineProps<{ products: any; filters: { search: string | null } }>();

const searchQuery = ref(props.filters?.search ?? '');

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/master/product', { search: val || undefined }, { preserveState: true, preserveScroll: true });
    }, 400);
});

const deleteProduct = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus product ini?')) {
        router.delete(`/master/product/${id}`);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Master Products" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <Heading variant="small" title="Master Products" description="Kelola produk untuk mini POS." />

                    <Link href="/master/product/create"
                        class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:opacity-90">
                        Tambah Product
                    </Link>
                </div>

                <!-- Search Bar -->
                <div class="relative w-full sm:w-64 ml-auto">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none">
                        <Search :size="16" />
                    </span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama produk..."
                        class="w-full h-10 pl-9 pr-4 rounded-xl border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                    />
                </div>

                <div class="rounded-2xl border bg-background shadow-sm overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr class="text-muted-foreground">
                                <th class="px-6 py-4 text-left font-medium">Nama</th>
                                <th class="px-6 py-4 text-left font-medium">Kategori</th>
                                <th class="px-6 py-4 text-left font-medium">Gym</th>
                                <th class="px-6 py-4 text-right font-medium">Buy</th>
                                <th class="px-6 py-4 text-right font-medium">Sell</th>
                                <th class="px-6 py-4 text-right font-medium">Stock</th>
                                <th class="px-6 py-4 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="product in products.data" :key="product.id" class="border-t hover:bg-muted/40 transition">
                                <td class="px-6 py-4 font-medium">{{ product.name }}</td>
                                <td class="px-6 py-4 text-muted-foreground">{{ product.category?.name || '-' }}</td>
                                <td class="px-6 py-4">{{ product.gym?.name || '-' }}</td>
                                <td class="px-6 py-4 text-right">{{ formatRupiah(product.buy_price) }}</td>
                                <td class="px-6 py-4 text-right">{{ formatRupiah(product.sell_price) }}</td>
                                <td class="px-6 py-4 text-right">{{ product.stock }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center gap-3">
                                        <Link :href="`/master/product/${product.id}/edit`"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-yellow-100 text-yellow-600 hover:bg-yellow-500 hover:text-white transition"
                                            title="Edit Product">
                                            <Pencil :size="16" />
                                        </Link>

                                        <button @click="deleteProduct(product.id)" type="button"
                                            class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition"
                                            title="Hapus Product">
                                            <Trash2 :size="16" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="products.data.length === 0">
                                <td colspan="7" class="px-6 py-10 text-center text-muted-foreground">Belum ada data produk.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 px-4" v-if="products.links">
                    <Pagination :links="products.links" preserveScroll />
                </div>

            </div>
        </div>
    </AppLayout>
</template>
