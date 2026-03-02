<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Input from "@/components/ui/input/Input.vue";
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    gymClass: any;
    gyms: { id: number; name: string }[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Gym Class', href: '/master/gym-class' },
    { title: 'Edit', href: `/master/gym-class/${props.gymClass.id}/edit` },
];

const form = useForm({
    gym_id: props.gymClass.gym_id,
    name: props.gymClass.name,
    description: props.gymClass.description ?? '',
    category: props.gymClass.category ?? '',
    default_capacity: props.gymClass.default_capacity,
    duration_minutes: props.gymClass.duration_minutes,
    image_url: null as File | null,
    is_active: props.gymClass.is_active,
});

const submit = () => {
    form.post(`/master/gym-class/${props.gymClass.id}`, {
        forceFormData: true,
        headers: { 'X-HTTP-Method-Override': 'PUT' },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Edit Kelas Gym" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-3xl mx-auto px-6">
                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">

                    <Heading variant="small" title="Edit Kelas Gym"
                        description="Perbarui informasi kelas gym." />

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

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Nama Kelas</label>
                            <Input v-model="form.name" />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Deskripsi</label>
                            <textarea v-model="form.description" rows="3"
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background" />
                        </div>

                        <div class="grid md:grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Kategori</label>
                                <Input v-model="form.category" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Kapasitas Default</label>
                                <Input v-model="form.default_capacity" type="number" />
                                <p v-if="form.errors.default_capacity" class="text-xs text-destructive">{{ form.errors.default_capacity }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Durasi (Menit)</label>
                                <Input v-model="form.duration_minutes" type="number" />
                                <p v-if="form.errors.duration_minutes" class="text-xs text-destructive">{{ form.errors.duration_minutes }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Gambar Kelas</label>
                            <div v-if="props.gymClass.image_url" class="mb-2">
                                <img :src="`/storage/${props.gymClass.image_url}`" alt="Current image"
                                    class="h-20 w-20 object-cover rounded-lg" />
                            </div>
                            <input type="file" accept="image/jpeg,image/png,image/jpg"
                                @change="(e: Event) => form.image_url = (e.target as HTMLInputElement).files?.[0] ?? null"
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                            <p v-if="form.errors.image_url" class="text-xs text-destructive">{{ form.errors.image_url }}</p>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="checkbox" v-model="form.is_active" id="is_active"
                                class="h-4 w-4 rounded border-gray-300" />
                            <label for="is_active" class="text-sm font-medium">Aktif</label>
                        </div>

                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <button type="submit" :disabled="form.processing"
                                class="cursor-pointer rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90 disabled:opacity-50 transition">
                                Update Kelas
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
