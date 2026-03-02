<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Input from "@/components/ui/input/Input.vue";
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

defineProps<{
    gymClasses: { id: number; name: string; default_capacity: number; duration_minutes: number }[];
    trainers: { id: number; name: string }[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Class Schedule', href: '/master/class-schedule' },
    { title: 'Create', href: '/master/class-schedule/create' },
];

const form = useForm({
    gym_class_id: '',
    trainer_id: '',
    date: '',
    start_time: '',
    end_time: '',
    location: '',
    capacity: 20,
    zoom_link: '',
});

const onClassChange = (classId: string) => {
    // Auto-fill capacity from selected class
    const gymClasses = document.querySelector<HTMLSelectElement>('[data-classes]');
    // Manual approach: just rely on user input or pass props
};

const submit = () => {
    form.post('/master/class-schedule');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Tambah Jadwal Kelas" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-3xl mx-auto px-6">
                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">

                    <Heading variant="small" title="Tambah Jadwal Kelas"
                        description="Buat jadwal baru untuk kelas gym." />

                    <form @submit.prevent="submit" class="space-y-6">

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Pilih Kelas</label>
                            <select v-model="form.gym_class_id" data-classes
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background">
                                <option value="" disabled>Pilih kelas gym</option>
                                <option v-for="cls in gymClasses" :key="cls.id" :value="cls.id">
                                    {{ cls.name }} ({{ cls.duration_minutes }}min, max {{ cls.default_capacity }})
                                </option>
                            </select>
                            <p v-if="form.errors.gym_class_id" class="text-xs text-destructive">{{ form.errors.gym_class_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Trainer (Opsional)</label>
                            <select v-model="form.trainer_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background">
                                <option value="">Tanpa Trainer</option>
                                <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">
                                    {{ trainer.name }}
                                </option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Tanggal</label>
                            <Input v-model="form.date" type="date" />
                            <p v-if="form.errors.date" class="text-xs text-destructive">{{ form.errors.date }}</p>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Jam Mulai</label>
                                <Input v-model="form.start_time" type="time" />
                                <p v-if="form.errors.start_time" class="text-xs text-destructive">{{ form.errors.start_time }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Jam Selesai</label>
                                <Input v-model="form.end_time" type="time" />
                                <p v-if="form.errors.end_time" class="text-xs text-destructive">{{ form.errors.end_time }}</p>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Lokasi</label>
                                <Input v-model="form.location" placeholder="Studio A, Lantai 2, dll" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Kapasitas</label>
                                <Input v-model="form.capacity" type="number" />
                                <p v-if="form.errors.capacity" class="text-xs text-destructive">{{ form.errors.capacity }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Zoom Link (untuk kelas online)</label>
                            <Input v-model="form.zoom_link" placeholder="https://zoom.us/j/..." />
                            <p v-if="form.errors.zoom_link" class="text-xs text-destructive">{{ form.errors.zoom_link }}</p>
                        </div>

                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <button type="submit" :disabled="form.processing"
                                class="cursor-pointer rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90 disabled:opacity-50">
                                Simpan Jadwal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
