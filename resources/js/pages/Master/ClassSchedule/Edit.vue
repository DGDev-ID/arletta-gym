<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Input from "@/components/ui/input/Input.vue";
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    schedule: any;
    gymClasses: { id: number; name: string; default_capacity: number; duration_minutes: number }[];
    trainers: { id: number; name: string }[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Class Schedule', href: '/master/class-schedule' },
    { title: 'Edit', href: `/master/class-schedule/${props.schedule.id}/edit` },
];

const form = useForm({
    gym_class_id: props.schedule.gym_class_id,
    trainer_id: props.schedule.trainer_id ?? '',
    date: props.schedule.date,
    start_time: props.schedule.start_time,
    end_time: props.schedule.end_time,
    location: props.schedule.location ?? '',
    capacity: props.schedule.capacity,
    zoom_link: props.schedule.zoom_link ?? '',
    is_cancelled: props.schedule.is_cancelled ?? false,
    cancel_reason: props.schedule.cancel_reason ?? '',
});

const submit = () => {
    form.put(`/master/class-schedule/${props.schedule.id}`);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Edit Jadwal Kelas" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-3xl mx-auto px-6">
                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">

                    <Heading variant="small" title="Edit Jadwal Kelas"
                        description="Perbarui jadwal kelas gym." />

                    <form @submit.prevent="submit" class="space-y-6">

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Pilih Kelas</label>
                            <select v-model="form.gym_class_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background">
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
                                <Input v-model="form.location" />
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

                        <hr class="my-4" />

                        <div class="space-y-4 p-4 border rounded-xl bg-red-50/50">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" v-model="form.is_cancelled" id="is_cancelled"
                                    class="h-4 w-4 rounded border-gray-300" />
                                <label for="is_cancelled" class="text-sm font-medium text-red-700">Batalkan Jadwal Ini</label>
                            </div>
                            <div v-if="form.is_cancelled" class="space-y-2">
                                <label class="text-sm font-medium">Alasan Pembatalan</label>
                                <textarea v-model="form.cancel_reason" rows="2"
                                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                                    placeholder="Masukkan alasan pembatalan..." />
                                <p v-if="form.errors.cancel_reason" class="text-xs text-destructive">{{ form.errors.cancel_reason }}</p>
                            </div>
                        </div>

                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <button type="submit" :disabled="form.processing"
                                class="cursor-pointer rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90 disabled:opacity-50 transition">
                                Update Jadwal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
