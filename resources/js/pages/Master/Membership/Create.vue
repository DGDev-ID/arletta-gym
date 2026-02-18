<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { type BreadcrumbItem } from '@/types';
import Input from "@/components/ui/input/Input.vue";
import { computed } from 'vue';

const props = defineProps<{
    gyms: { id: number; name: string }[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Membership', href: '/master/membership' },
    { title: 'Create', href: '/master/membership/create' },
];

const form = useForm({
    gym_id: '',
    name: '',
    duration_in_days: '',
    price: '',
    promos: [
        { unique_code: '', type: 'discount_percent', value: '' }
    ],
});

const addPromo = () => {
    form.promos.push({ unique_code: '', type: 'discount_percent', value: '' });
};

const removePromo = (index: number) => {
    form.promos.splice(index, 1);
};

const submit = () => {
    form.post('/master/membership');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Tambah Membership" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-3xl mx-auto px-6">
                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">

                    <Heading variant="small" title="Tambah Membership Baru"
                        description="Tentukan paket membership dan promo opsional." />

                    <form @submit.prevent="submit" class="space-y-6">

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Pilih Gym</label>
                            <select v-model="form.gym_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background">
                                <option value="" disabled>Pilih lokasi gym</option>
                                <option v-for="gym in gyms" :key="gym.id" :value="gym.id">
                                    {{ gym.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.gym_id" class="text-xs text-destructive">{{ form.errors.gym_id }}</p>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Nama Paket</label>
                                <Input v-model="form.name" placeholder="Contoh: Gold Monthly" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Harga (IDR)</label>
                                <Input v-model="form.price" type="number" placeholder="500000" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Durasi (Hari)</label>
                            <Input v-model="form.duration_in_days" type="number" placeholder="30" />
                        </div>

                        <hr class="my-6" />

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-sm">Daftar Promo (Opsional)</h3>
                                <button type="button" @click="addPromo"
                                    class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1 rounded-lg border border-indigo-200">
                                    + Tambah Promo
                                </button>
                            </div>

                            <div v-for="(promo, index) in form.promos" :key="index"
                                class="p-4 border rounded-xl bg-muted/20 relative space-y-4">
                                <button v-if="form.promos.length > 1" type="button" @click="removePromo(index)"
                                    class="absolute -top-2 -right-2 bg-destructive text-white rounded-full w-6 h-6 text-xs flex items-center justify-center">
                                    ✕
                                </button>

                                <div class="space-y-2">
                                    <label class="text-xs font-medium">Kode Unik Promo <br> *Masukkan GLOBAL jika promo berlaku global</label>
                                    <Input v-model="promo.unique_code" :placeholder="`PROMO-${index + 1}`" />
                                    <p v-if="form.errors[`promos.${index}.unique_code`]"
                                        class="text-xs text-destructive">
                                        {{ form.errors[`promos.${index}.unique_code`] }}
                                    </p>
                                </div>

                                <div class="grid md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="text-xs font-medium">Tipe</label>
                                        <select v-model="promo.type"
                                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                            <option value="discount_percent">Diskon (%)</option>
                                            <option value="discount_amount">Potongan Harga (Rp)</option>
                                            <option value="bonus_days">Bonus Hari</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-medium">Nilai</label>
                                        <Input v-model="promo.value" type="number" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <Link href="/master/membership" class="text-sm text-muted-foreground hover:underline">
                                Batal
                            </Link>
                            <button type="submit" :disabled="form.processing"
                                class="rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90 disabled:opacity-50">
                                Simpan Membership
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>