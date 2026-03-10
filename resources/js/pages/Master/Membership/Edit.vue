<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Input from "@/components/ui/input/Input.vue";
import Textarea from "@/components/ui/textarea/Textarea.vue";
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    gyms: { id: number; name: string }[];
    membership: any;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Membership', href: '/master/membership' },
    { title: 'Edit', href: `/master/membership/${props.membership.id}/edit` },
];

const form = useForm({
    gym_id: props.membership.gym_id,
    name: props.membership.name,
    description: props.membership.description || '',
    duration_in_days: props.membership.duration_in_days,
    price: props.membership.price,
    // Inisialisasi array promos dari relasi (asumsi nama relasi: membership_promos)
    promos: props.membership.membership_promos && props.membership.membership_promos.length > 0
        ? props.membership.membership_promos.map((p: any) => ({
            unique_code: p.unique_code,
            type: p.type,
            value: p.value
        }))
        : [{ unique_code: '', type: 'discount_percent', value: '' }],
});

const addPromo = () => {
    form.promos.push({ unique_code: '', type: 'discount_percent', value: '' });
};

const removePromo = (index: number) => {
    form.promos.splice(index, 1);
};

const submit = () => {
    form.put(`/master/membership/${props.membership.id}`);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Edit Membership" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-3xl mx-auto px-6">
                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">

                    <Heading variant="small" title="Edit Membership"
                        description="Perbarui informasi paket membership dan promo." />

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Pilih Gym</label>
                            <select v-model="form.gym_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background">
                                <option v-for="gym in gyms" :key="gym.id" :value="gym.id">
                                    {{ gym.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.gym_id" class="text-xs text-destructive">{{ form.errors.gym_id }}</p>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Nama Paket</label>
                                <Input v-model="form.name" />
                                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Harga (IDR)</label>
                                <Input v-model="form.price" type="number" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Deskripsi (Opsional)</label>
                            <Textarea v-model="form.description" rows="3" />
                            <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Durasi (Hari)</label>
                            <Input v-model="form.duration_in_days" type="number" />
                        </div>

                        <hr class="my-6" />

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
                                    <Input v-model="promo.unique_code" />

                                    <p v-if="(form.errors as any)[`promos.${index}.unique_code`]"
                                        class="text-xs text-destructive">
                                        {{ (form.errors as any)[`promos.${index}.unique_code`] }}
                                    </p>
                                </div>

                                <div class="grid md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-gray-500">Tipe</label>
                                        <select v-model="promo.type"
                                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary/20">
                                            <option value="discount_percent">Diskon (%)</option>
                                            <option value="discount_amount">Potongan Harga (Rp)</option>
                                            <option value="bonus_days">Bonus Hari</option>
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
                                class="cursor-pointer rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90 disabled:opacity-50 transition">
                                Update Membership
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>