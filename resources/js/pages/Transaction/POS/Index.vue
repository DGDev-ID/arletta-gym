<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import Input from '@/components/ui/input/Input.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    gyms: { id: number; name: string }[];
    products: any[];
    pendingTransactions: any[];
    selectedGymId: number | null;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'POS', href: '/transaction/pos' },
];

const selectedGym = ref(props.selectedGymId ?? (props.gyms && props.gyms[0] ? props.gyms[0].id : null));

const cart = ref<Record<number, number>>({});

const form = useForm({ items: [] });

const products = computed(() => props.products || []);

const cartItems = computed(() => {
    return Object.entries(cart.value).map(([productId, qty]) => {
        const product = products.value.find(p => p.id === Number(productId));
        return { product, quantity: qty };
    }).filter(i => i.product);
});

const cartTotal = computed(() => cartItems.value.reduce((sum, it) => sum + (it.product.sell_price * it.quantity), 0));

const changeGym = () => {
    router.get('/transaction/pos', { gym_id: selectedGym.value }, { preserveState: true });
    cart.value = {};
};

const inc = (product: any) => {
    cart.value[product.id] = (cart.value[product.id] || 0) + 1;
};

const dec = (product: any) => {
    if (!cart.value[product.id]) return;
    cart.value[product.id] = cart.value[product.id] - 1;
    if (cart.value[product.id] <= 0) delete cart.value[product.id];
};

const openCheckout = ref(false);

const generatePayment = () => {
    form.items = cartItems.value.map(i => ({ product_id: i.product.id, quantity: i.quantity }));
    form.post('/transaction/pos');
};

const makeSuccess = (id: number) => {
    if (!confirm('Tandai transaksi ini berhasil dan kurangi stok?')) return;
    router.post(`/transaction/pos/${id}/make-success`);
};

const makeFailed = (id: number) => {
    if (!confirm('Tandai transaksi ini gagal dan hapus log?')) return;
    router.post(`/transaction/pos/${id}/make-failed`);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="POS" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <div class="flex items-center justify-between">
                    <Heading variant="small" title="POS" description="Mini Point of Sale" />
                </div>

                <div class="grid md:grid-cols-3 gap-6">

                    <div class="md:col-span-2 space-y-4">
                        <div class="flex items-center gap-4">
                            <label class="text-sm font-medium">Pilih Gym</label>
                            <select v-model="selectedGym" @change="changeGym"
                                class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option v-for="g in props.gyms" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                        </div>

                        <div class="rounded-2xl border bg-background shadow-sm overflow-hidden">
                            <table class="min-w-full text-sm">
                                <thead class="bg-muted/50">
                                    <tr class="text-muted-foreground">
                                        <th class="px-6 py-4 text-left font-medium">Nama</th>
                                        <th class="px-6 py-4 text-left font-medium">Kategori</th>
                                        <th class="px-6 py-4 text-right font-medium">Harga</th>
                                        <th class="px-6 py-4 text-right font-medium">Stok</th>
                                        <th class="px-6 py-4 text-right font-medium">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="prod in products" :key="prod.id" class="border-t hover:bg-muted/40 transition">
                                        <td class="px-6 py-4 font-medium">{{ prod.name }}</td>
                                        <td class="px-6 py-4 text-muted-foreground">{{ prod.category?.name || '-' }}</td>
                                        <td class="px-6 py-4 text-right">{{ prod.sell_price }}</td>
                                        <td class="px-6 py-4 text-right">{{ prod.stock }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end items-center gap-3">
                                                <button @click="dec(prod)" type="button"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition"
                                                    title="Kurangi">
                                                    -
                                                </button>

                                                <div class="w-8 text-center font-medium">{{ cart[prod.id] || 0 }}</div>

                                                <button @click="inc(prod)" type="button"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-green-100 text-green-700 hover:bg-green-700 hover:text-white transition"
                                                    title="Tambah">
                                                    +
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="products.length === 0">
                                        <td colspan="5" class="px-6 py-10 text-center text-muted-foreground">Tidak ada produk.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="rounded-2xl border bg-background shadow-sm overflow-hidden">
                            <div class="p-6 border-b">
                                <h3 class="font-semibold">Pending Transactions</h3>
                            </div>

                            <table class="min-w-full text-sm">
                                <thead class="bg-muted/50">
                                    <tr class="text-muted-foreground">
                                        <th class="px-6 py-4 text-left font-medium">Waktu</th>
                                        <th class="px-6 py-4 text-left font-medium">Items</th>
                                        <th class="px-6 py-4 text-right font-medium">Total</th>
                                        <th class="px-6 py-4 text-right font-medium">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="trx in props.pendingTransactions" :key="trx.id" class="border-t hover:bg-muted/40 transition">
                                        <td class="px-6 py-4">{{ trx.created_at }}</td>
                                        <td class="px-6 py-4">
                                            <ul class="text-sm">
                                                <li v-for="p in trx.products" :key="p.id">{{ p.product?.name }} x {{ p.quantity }}</li>
                                            </ul>
                                        </td>
                                        <td class="px-6 py-4 text-right">{{ trx.total_price }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end items-center gap-2">
                                                <button @click="makeSuccess(trx.id)" class="inline-flex items-center justify-center rounded-xl bg-green-50 px-3 py-1.5 text-sm font-medium text-green-700 hover:bg-green-600 hover:text-white transition">Make Success</button>
                                                <button @click="makeFailed(trx.id)" class="inline-flex items-center justify-center rounded-xl bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-600 hover:text-white transition">Make Failed</button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="props.pendingTransactions.length === 0">
                                        <td colspan="4" class="px-6 py-10 text-center text-muted-foreground">Tidak ada transaksi pending.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>

                    <div class="space-y-4">
                        <div class="rounded-2xl border bg-background shadow-sm p-4 w-80">
                            <h3 class="font-semibold">Keranjang</h3>
                            <div v-if="cartItems.length > 0" class="space-y-2 mt-3">
                                <div v-for="it in cartItems" :key="it.product.id" class="flex justify-between">
                                    <div>
                                        <div class="font-medium">{{ it.product.name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ it.quantity }} x {{ it.product.sell_price }}</div>
                                    </div>
                                    <div class="font-medium">{{ (it.product.sell_price * it.quantity).toFixed(2) }}</div>
                                </div>

                                <hr />
                                <div class="flex justify-between font-semibold mt-2">Total <div>{{ cartTotal.toFixed(2) }}</div></div>

                                <div class="mt-4 flex gap-2">
                                    <button @click="openCheckout = true" class="flex-1 inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90">Checkout</button>
                                    <button @click="cart = {}" class="inline-flex items-center justify-center rounded-xl border px-5 py-2.5 text-sm">Clear</button>
                                </div>
                            </div>
                            <div v-else class="text-sm text-muted-foreground mt-3">Keranjang kosong</div>
                        </div>

                        <div v-if="openCheckout" class="fixed right-8 top-24 w-96 rounded-2xl border bg-background shadow-lg p-6">
                            <h3 class="font-semibold">Checkout</h3>
                            <div class="mt-3 space-y-2">
                                <div v-for="it in cartItems" :key="it.product.id" class="flex justify-between">
                                    <div>{{ it.product.name }} x {{ it.quantity }}</div>
                                    <div>{{ (it.product.sell_price * it.quantity).toFixed(2) }}</div>
                                </div>
                                <hr />
                                <div class="flex justify-between font-semibold">Total <div>{{ cartTotal.toFixed(2) }}</div></div>
                                <div class="mt-4 flex gap-2">
                                    <button @click="generatePayment" class="flex-1 inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90">Generate Pembayaran</button>
                                    <button @click="openCheckout = false" class="inline-flex items-center justify-center rounded-xl border px-5 py-2.5 text-sm">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
