<script setup lang="ts">
import { ref, watch } from 'vue';
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

const DAY_NAMES = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Determine initial trainer mode
const trainerMode = ref<'user' | 'manual'>(
    props.schedule.trainer_id ? 'user' : (props.schedule.trainer_name ? 'manual' : 'user')
);

const form = useForm({
    gym_class_id:           props.schedule.gym_class_id,
    trainer_id:             props.schedule.trainer_id ?? '' as number | '',
    trainer_name:           props.schedule.trainer_name ?? '',
    date:                   props.schedule.date,
    start_time:             props.schedule.start_time,
    end_time:               props.schedule.end_time,
    location:               props.schedule.location ?? '',
    capacity:               props.schedule.capacity,
    zoom_link:              props.schedule.zoom_link ?? '',
    is_cancelled:           props.schedule.is_cancelled ?? false,
    cancel_reason:          props.schedule.cancel_reason ?? '',
    is_recurring:           props.schedule.is_recurring ?? false,
    recurring_day_of_week:  props.schedule.recurring_day_of_week ?? null as number | null,
});

watch(() => form.gym_class_id, (newId) => {
    const selected = props.gymClasses.find(cls => cls.id === Number(newId));
    if (selected) {
        form.capacity = selected.default_capacity;
    }
});

watch(trainerMode, (mode) => {
    if (mode === 'user') {
        form.trainer_name = '';
    } else {
        form.trainer_id = '';
    }
});

watch(() => form.is_recurring, (val) => {
    if (!val) form.recurring_day_of_week = null;
});

const submit = () => {
    form.put(`/master/class-schedule/${props.schedule.id}`);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Edit Jadwal Kelas" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6">
                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">

                    <Heading variant="small" title="Edit Jadwal Kelas"
                        description="Perbarui jadwal kelas gym." />

                    <form @submit.prevent="submit" class="space-y-6">

                        <!-- Pilih Kelas -->
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

                        <!-- Trainer -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Trainer (Opsional)</label>
                            <div class="flex gap-2 mb-2">
                                <button type="button"
                                    @click="trainerMode = 'user'"
                                    :class="trainerMode === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                                    class="px-3 py-1 rounded-md text-xs font-medium transition">
                                    Pilih dari Trainer
                                </button>
                                <button type="button"
                                    @click="trainerMode = 'manual'"
                                    :class="trainerMode === 'manual' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                                    class="px-3 py-1 rounded-md text-xs font-medium transition">
                                    Input Nama Manual
                                </button>
                            </div>
                            <select v-if="trainerMode === 'user'" v-model="form.trainer_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background">
                                <option value="">Tanpa Trainer</option>
                                <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">
                                    {{ trainer.name }}
                                </option>
                            </select>
                            <Input v-else v-model="form.trainer_name" placeholder="Nama trainer..." />
                            <p v-if="form.errors.trainer_name" class="text-xs text-destructive">{{ form.errors.trainer_name }}</p>
                        </div>

                        <!-- Recurring -->
                        <div class="space-y-3 p-4 border rounded-xl bg-blue-50/40">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" v-model="form.is_recurring" id="is_recurring"
                                    class="h-4 w-4 rounded border-gray-300" />
                                <label for="is_recurring" class="text-sm font-medium">Jadwal Berulang (Recurring)</label>
                            </div>
                            <p class="text-xs text-muted-foreground">Jika diaktifkan, jadwal ini akan muncul terus setiap minggu pada hari yang dipilih. Slot otomatis reset setelah melewati hari tersebut.</p>
                            <div v-if="form.is_recurring" class="space-y-2">
                                <label class="text-sm font-medium">Hari Berulang</label>
                                <select v-model="form.recurring_day_of_week"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background">
                                    <option :value="null" disabled>Pilih hari</option>
                                    <option v-for="(day, idx) in DAY_NAMES" :key="idx" :value="idx">{{ day }}</option>
                                </select>
                                <p v-if="form.errors.recurring_day_of_week" class="text-xs text-destructive">{{ form.errors.recurring_day_of_week }}</p>
                            </div>
                        </div>

                        <!-- Tanggal -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Tanggal{{ form.is_recurring ? ' (berikutnya)' : '' }}</label>
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
                                <Input v-model="form.capacity" type="number" disabled class="cursor-not-allowed opacity-60" />
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

