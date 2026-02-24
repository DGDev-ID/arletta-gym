<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Heading from '@/components/Heading.vue';

// Import UI Components
import Input from "@/components/ui/input/Input.vue"
import MultipleSelect from "@/components/ui/multiple-select/MultipleSelect.vue"
import Textarea from "@/components/ui/textarea/Textarea.vue"
import AppLayout from '@/layouts/AppLayout.vue';
import type { Gym, GymImage } from '@/types';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    gym: Gym;
    users: { id: number; name: string }[];
    personalTrainers: { id: number; name: string }[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Gyms', href: '/master/gym' },
    { title: 'Edit Gym', href: `/master/gym/${props.gym.id}/edit` },
];

// Map users untuk MultipleSelect
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

const currentImages = ref<GymImage[]>(props.gym.gym_images || []);

const form = useForm({
    _method: 'PUT',
    name: props.gym.name,
    address: props.gym.address,
    address_coordinate: props.gym.address_coordinate ?? '',
    description: props.gym.description ?? '',
    start_access: props.gym.start_access,
    admin_ids: props.gym.admins?.map((admin) => admin.id) || [],
    personal_trainer_ids: props.gym.personal_trainers?.map((trainer) => trainer.id) || [],
    images: [] as File[],
    deleted_image_ids: [] as number[],
});

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files) {
        form.images = Array.from(target.files);
    }
};

const removeExistingImage = (id: number) => {
    form.deleted_image_ids.push(id);
    currentImages.value = currentImages.value.filter((img) => img.id !== id);
};

const submit = () => {
    // Tetap gunakan post dengan _method PUT untuk mendukung upload file
    form.post(`/master/gym/${props.gym.id}`);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Edit Gym" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-3xl mx-auto px-6">
                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">

                    <Heading variant="small" title="Edit Data Gym"
                        description="Perbarui informasi detail untuk gym ini." />

                    <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Nama Gym</label>
                            <Input v-model="form.name" :aria-invalid="!!form.errors.name" />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Alamat</label>
                            <Textarea v-model="form.address" rows="3" :aria-invalid="!!form.errors.address" />
                            <p v-if="form.errors.address" class="text-sm text-destructive">{{ form.errors.address }}</p>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Koordinat (Opsional)</label>
                                <Input v-model="form.address_coordinate" placeholder="-7.797068, 110.370529" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Tanggal Akses Dibuka</label>
                                <Input v-model="form.start_access" type="date"
                                    :aria-invalid="!!form.errors.start_access" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Deskripsi (Opsional)</label>
                            <Textarea v-model="form.description" rows="3" />
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Pilih Admin</label>
                            <MultipleSelect v-model="form.admin_ids" :options="adminOptions"
                                placeholder="Cari admin..." />
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Pilih Personal Trainer</label>
                            <MultipleSelect v-model="form.personal_trainer_ids" :options="personalTrainerOptions"
                                placeholder="Cari personal trainer..." />
                        </div>

                        <div class="pt-4 border-t space-y-4">
                            <h3 class="text-sm font-semibold">Foto Gym Saat Ini</h3>

                            <div v-if="currentImages.length > 0" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div v-for="image in currentImages" :key="image.id"
                                    class="relative group rounded-xl overflow-hidden border bg-muted">
                                    <img :src="`/storage/${image.img_url}`"
                                        class="h-32 w-full object-cover transition group-hover:scale-105"
                                        alt="Gym Photo" />

                                    <button type="button" @click="removeExistingImage(image.id)"
                                        class="absolute top-2 right-2 rounded-full bg-destructive/90 p-1.5 text-white opacity-0 group-hover:opacity-100 transition shadow-sm">
                                        <span class="sr-only">Hapus</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 6 6 18" />
                                            <path d="m6 6 12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <p v-else class="text-sm text-muted-foreground italic">Belum ada foto yang diunggah.</p>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Upload Foto Baru (Multiple)</label>
                                <input type="file" multiple @change="handleFileChange" accept="image/*"
                                    class="block w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer" />

                                <progress v-if="form.progress" :value="form.progress.percentage" max="100"
                                    class="w-full h-2 rounded overflow-hidden">
                                    {{ form.progress.percentage }}%
                                </progress>
                                <p v-if="form.errors['images']" class="text-sm text-destructive">{{
                                    form.errors['images'] }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4 border-t pt-6">
                            <Link href="/master/gym" class="text-sm text-muted-foreground hover:underline">
                                Batal
                            </Link>
                            <button type="submit" :disabled="form.processing"
                                class="rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90 disabled:opacity-50 transition-opacity">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>