<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Input from "@/components/ui/input/Input.vue";
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    personal_trainer: {
        id: number,
        name: string,
        description: string | null,
        gyms: { id: number; name: string }[],
        experience: string,
        experience_years: number | null,
        certifications: string[],
        specializations: string[],
        instagram: string,
        rating: number | null,
    };
    gyms: { id: number; name: string }[];
}>();

const breadcrumbItems = [
    { title: 'Management Personal Trainer', href: '/management/personal-trainer' },
    { title: 'Edit', href: `/management/personal-trainer/${props.personal_trainer.id}/edit` },
];

const form = useForm({
    gym_id: props.personal_trainer.gyms && props.personal_trainer.gyms.length > 0 ? props.personal_trainer.gyms[0].id : (props.gyms && props.gyms.length > 0 ? props.gyms[0].id : null),
    description: props.personal_trainer.description ?? '',
    experience: props.personal_trainer.experience ?? '',
    experience_years: props.personal_trainer.experience_years ?? null,
    certifications: Array.isArray(props.personal_trainer.certifications) ? props.personal_trainer.certifications.join('\n') : '',
    specializations: Array.isArray(props.personal_trainer.specializations) ? props.personal_trainer.specializations.join('\n') : '',
    instagram: props.personal_trainer.instagram ?? '',
    rating: props.personal_trainer.rating ?? null,
});

const submit = () => {
    form.put(`/management/personal-trainer/${props.personal_trainer.id}`);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Edit Personal Trainer" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-3xl mx-auto px-6">
                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">

                    <Heading variant="small" title="Edit Personal Trainer"
                        description="Perbarui data profil dan deskripsi Personal Trainer." />

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Nama Personal Trainer</label>
                            <div class="py-2 px-3 rounded-md border bg-muted/10">{{ props.personal_trainer.name }}</div>
                        </div>

                        <!-- Per-Gym Section -->
                        <div class="border-t pt-6">
                            <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">Data Per Gym</h3>

                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Pilih Gym</label>
                                    <select v-model="form.gym_id"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background">
                                        <option v-for="gym in props.gyms" :key="gym.id" :value="gym.id">{{ gym.name }}</option>
                                    </select>
                                    <p v-if="form.errors.gym_id" class="text-xs text-destructive">{{ form.errors.gym_id }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Deskripsi / Bio</label>
                                    <textarea v-model="form.description" rows="4"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        placeholder="Deskripsi singkat tentang trainer ini..."></textarea>
                                    <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Global PT Profile Section -->
                        <div class="border-t pt-6">
                            <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">Profil Trainer</h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Pengalaman</label>
                                    <Input v-model="form.experience" placeholder="Contoh: 7 years" />
                                    <p v-if="form.errors.experience" class="text-xs text-destructive">{{ form.errors.experience }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Tahun Pengalaman (angka)</label>
                                    <Input v-model.number="form.experience_years" type="number" min="0" max="100" placeholder="Contoh: 7" />
                                    <p v-if="form.errors.experience_years" class="text-xs text-destructive">{{ form.errors.experience_years }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Instagram</label>
                                    <Input v-model="form.instagram" placeholder="@username" />
                                    <p v-if="form.errors.instagram" class="text-xs text-destructive">{{ form.errors.instagram }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Rating</label>
                                    <Input v-model.number="form.rating" type="number" step="0.1" min="0" max="5" placeholder="0.0 - 5.0" />
                                    <p v-if="form.errors.rating" class="text-xs text-destructive">{{ form.errors.rating }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Spesialisasi</label>
                                    <textarea v-model="form.specializations" rows="4"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        placeholder="Satu per baris, contoh:&#10;Fat loss&#10;Muscle gain&#10;HIIT"></textarea>
                                    <p v-if="form.errors.specializations" class="text-xs text-destructive">{{ form.errors.specializations }}</p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Sertifikasi</label>
                                    <textarea v-model="form.certifications" rows="4"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        placeholder="Satu per baris, contoh:&#10;Personal Trainer Foundation&#10;First Aid Level 1"></textarea>
                                    <p v-if="form.errors.certifications" class="text-xs text-destructive">{{ form.errors.certifications }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <button type="submit" :disabled="form.processing"
                                class="cursor-pointer rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90 disabled:opacity-50 transition">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
