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
        gyms: { id: number; name: string }[]
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
                        description="Perbarui deskripsi Personal Trainer untuk gym yang dipilih." />

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Nama Personal Trainer</label>
                            <div class="py-2 px-3 rounded-md border bg-muted/10">{{ props.personal_trainer.name }}</div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Pilih Gym</label>
                            <select v-model="form.gym_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background">
                                <option v-for="gym in props.gyms" :key="gym.id" :value="gym.id">{{ gym.name }}</option>
                            </select>
                            <p v-if="form.errors.gym_id" class="text-xs text-destructive">{{ form.errors.gym_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Deskripsi</label>
                            <textarea v-model="form.description" rows="6"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
                            <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                        </div>

                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <button type="submit" :disabled="form.processing"
                                class="cursor-pointer rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90 disabled:opacity-50 transition">
                                Simpan Deskripsi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
