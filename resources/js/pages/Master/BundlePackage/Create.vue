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
});

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
