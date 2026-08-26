<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Input from "@/components/ui/input/Input.vue";
import Textarea from "@/components/ui/textarea/Textarea.vue";
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

defineProps<{
    gyms: { id: number; name: string }[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Bundle Package', href: '/master/bundle-package' },
    { title: 'Create', href: '/master/bundle-package/create' },
];

const form = useForm({
    gym_id: '',
    name: '',
    description: '',
    membership_duration_in_days: '',
    pt_sessions: '',
    price: '',
    promos: [{ unique_code: '', type: 'discount_percent', value: '' }] as { unique_code: string; type: string; value: string | number }[],
});

const addPromo = () => {
    form.promos.push({ unique_code: '', type: 'discount_percent', value: '' });
};

const removePromo = (index: number) => {
    form.promos.splice(index, 1);
};

const submit = () => {
    form.post('/master/bundle-package');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Tambah Paket Bundling" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6">
                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">

                    <Heading variant="small" title="Tambah Paket Bundling Baru"
                        description="Buat paket yang menggabungkan membership dan sesi personal trainer dalam satu harga." />

                    <form @submit.prevent="submit" class="space-y-6">

                        <!-- Pilih Gym -->
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

                        <!-- Nama & Harga -->
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Nama Paket</label>
                                <Input v-model="form.name" placeholder="Contoh: Paket A" />
                                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Harga Bundle (IDR)</label>
                                <Input v-model="form.price" type="number" placeholder="1500000" />
                                <p v-if="form.errors.price" class="text-xs text-destructive">{{ form.errors.price }}</p>
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Deskripsi (Opsional)</label>
                            <Textarea v-model="form.description" rows="3"
                                placeholder="Deskripsi singkat tentang paket bundling ini..." />
                            <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                        </div>

                        <hr class="my-2" />

                        <!-- Isi Bundle -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-semibold">Isi Bundling</h3>
                            <div class="rounded-xl border bg-muted/20 p-5 space-y-4">
                                <div class="grid md:grid-cols-2 gap-6">
                                    <!-- Durasi Membership -->
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Durasi Membership (Hari)</label>
                                        <Input v-model="form.membership_duration_in_days" type="number"
                                            placeholder="Contoh: 90 (= 3 bulan)" />
                                        <p class="text-xs text-muted-foreground">30 hari = 1 bulan, 90 hari = 3 bulan</p>
                                        <p v-if="form.errors.membership_duration_in_days" class="text-xs text-destructive">
                                            {{ form.errors.membership_duration_in_days }}
                                        </p>
                                    </div>
                                    <!-- Jumlah Sesi PT -->
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Jumlah Sesi Personal Trainer</label>
                                        <Input v-model="form.pt_sessions" type="number"
                                            placeholder="Contoh: 5" />
                                        <p class="text-xs text-muted-foreground">Jumlah pertemuan dengan PT yang termasuk</p>
                                        <p v-if="form.errors.pt_sessions" class="text-xs text-destructive">
                                            {{ form.errors.pt_sessions }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Preview -->
                                <div v-if="form.membership_duration_in_days || form.pt_sessions || form.price"
                                    class="mt-4 rounded-lg bg-primary/5 border border-primary/20 p-4">
                                    <p class="text-xs font-semibold text-primary mb-2">Preview Paket:</p>
                                    <ul class="text-sm space-y-1 text-muted-foreground">
                                        <li v-if="form.membership_duration_in_days">
                                             Membership selama <strong>{{ form.membership_duration_in_days }} hari</strong>
                                        </li>
                                        <li v-if="form.pt_sessions">
                                             <strong>{{ form.pt_sessions }} sesi</strong> Personal Trainer
                                        </li>
                                        <li v-if="form.price" class="font-semibold text-green-700">
                                             Harga Bundle: Rp {{ Number(form.price).toLocaleString('id-ID') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6" />

                        <!-- Daftar Promo -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-sm italic text-muted-foreground">Daftar Promo</h3>
                                <button type="button" @click="addPromo"
                                    class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-lg border border-indigo-200 hover:bg-indigo-100 transition">
                                    + Tambah Promo
                                </button>
                            </div>

                            <div v-for="(promo, index) in form.promos" :key="index"
                                class="p-5 border rounded-xl bg-muted/20 relative space-y-4 shadow-sm">

                                <button v-if="form.promos.length > 0" type="button" @click="removePromo(Number(index))"
                                    class="absolute -top-2 -right-2 bg-destructive text-white rounded-full w-6 h-6 text-xs flex items-center justify-center hover:bg-destructive/90 shadow">
                                    ✕
                                </button>

                                <div class="space-y-2">
                                    <label class="text-xs font-medium">Kode Unik Promo</label>
                                    <Input
                                        v-model="promo.unique_code"
                                        placeholder="Kosongkan untuk promo global (otomatis aktif)"
                                        style="text-transform: uppercase"
                                        @keydown.space.prevent
                                        @input="(e: any) => { promo.unique_code = e.target.value.toUpperCase().replace(/\s/g, '') }"
                                    />
                                    <p class="text-[11px] text-muted-foreground">Kosongkan = promo aktif otomatis untuk semua. Isi = perlu kode khusus.</p>
                                    <p v-if="(form.errors as any)[`promos.${index}.unique_code`]"
                                        class="text-xs text-destructive">
                                        {{ (form.errors as any)[`promos.${index}.unique_code`] }}
                                    </p>
                                </div>

                                <div class="grid md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="text-xs font-medium uppercase tracking-wider text-gray-500">Tipe</label>
                                        <select v-model="promo.type"
                                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary/20">
                                            <option value="discount_percent">Diskon (%)</option>
                                            <option value="discount_amount">Potongan Harga (Rp)</option>
                                            <option value="bonus_days">Bonus Hari Membership</option>
                                            <option value="bonus_sessions">Bonus Sesi PT</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-medium">Nilai</label>
                                        <Input v-model="promo.value" type="number" />
                                        <p v-if="(form.errors as any)[`promos.${index}.value`]"
                                            class="text-xs text-destructive">
                                            {{ (form.errors as any)[`promos.${index}.value`] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <button type="submit" :disabled="form.processing"
                                class="cursor-pointer rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90 disabled:opacity-50">
                                Simpan Paket Bundling
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
