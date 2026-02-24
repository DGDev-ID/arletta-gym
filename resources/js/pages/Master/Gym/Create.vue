<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import Input from "@/components/ui/input/Input.vue"
import MultipleSelect from "@/components/ui/multiple-select/MultipleSelect.vue"
import Textarea from "@/components/ui/textarea/Textarea.vue"
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';


const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Gyms', href: '/master/gym' },
    { title: 'Create', href: '/master/gym/create' },
];

const props = defineProps<{
    users: { id: number; name: string }[];
    personalTrainers: { id: number; name: string }[];
}>();

const adminOptions = computed(() =>
    props.users.map((user) => ({
        label: user.name,
        value: user.id,
    }))
);

const personalTrainerOptions = computed(() =>
    props.personalTrainers.map((user) => ({
        label: user.name,
        value: user.id,
    }))
);

const form = useForm({
    name: '',
    address: '',
    address_coordinate: '',
    description: '',
    start_access: '',
    admin_ids: [] as number[],
    personal_trainer_ids: [] as number[],
});

const submit = () => {
    form.post('/master/gym');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Create Gym" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-3xl mx-auto px-6">

                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">

                    <Heading variant="small" title="Tambah Gym Baru"
                        description="Masukkan informasi detail untuk gym baru." />

                    <form @submit.prevent="submit" class="space-y-6">

                        <!-- Nama -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Nama Gym</label>

                            <Input v-model="form.name" :aria-invalid="!!form.errors.name" />

                            <p v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Alamat -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Alamat</label>
                            <Textarea v-model="form.address" rows="3" />
                        </div>

                        <!-- Grid -->
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Koordinat</label>
                                <Input v-model="form.address_coordinate" placeholder="-7.797068, 110.370529" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Tanggal Akses</label>
                                <Input v-model="form.start_access" type="date"
                                    :aria-invalid="!!form.errors.start_access" />
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Deskripsi</label>
                            <Textarea v-model="form.description" rows="3" />
                        </div>

                        <!-- Admin -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Pilih Admin</label>

                            <MultipleSelect v-model="form.admin_ids" :options="adminOptions"
                                placeholder="Cari admin..." />
                        </div>

                        <!-- Personal Trainer -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Pilih Personal Trainer</label>

                            <MultipleSelect v-model="form.personal_trainer_ids" :options="personalTrainerOptions"
                                placeholder="Cari personal trainer..." />
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <button type="submit" :disabled="form.processing"
                                class="cursor-pointer rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90 disabled:opacity-50">
                                Simpan Gym
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
