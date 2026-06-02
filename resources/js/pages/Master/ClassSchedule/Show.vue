<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Trash2, UserPlus, ArrowLeft, X } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const DAY_NAMES = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

const props = defineProps<{
    schedule: any;
    bookings: any[];
    users: { id: number; name: string; email: string }[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Master Class Schedule', href: '/master/class-schedule' },
    { title: 'Peserta Kelas', href: `/master/class-schedule/${props.schedule.id}` },
];

const flash = computed(() => (usePage().props as any).flash ?? {});

// Participant mode: 'user' = pilih dari daftar member, 'guest' = input manual
const participantMode = ref<'user' | 'guest'>('user');

// Autocomplete state
const userSearch = ref('');
const showDropdown = ref(false);
const selectedUser = ref<{ id: number; name: string; email: string } | null>(null);

const filteredUsers = computed(() => {
    if (!userSearch.value) return props.users.slice(0, 30);
    const q = userSearch.value.toLowerCase();
    return props.users.filter(
        u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
    ).slice(0, 30);
});

const selectUser = (user: { id: number; name: string; email: string }) => {
    selectedUser.value = user;
    form.user_id = user.id;
    userSearch.value = '';
    showDropdown.value = false;
};

const clearUser = () => {
    selectedUser.value = null;
    form.user_id = null;
    userSearch.value = '';
};

const onSearchFocus = () => { showDropdown.value = true; };
const onSearchBlur = () => { setTimeout(() => { showDropdown.value = false; }, 150); };

const form = useForm({
    user_id:      null as number | null,
    guest_name:   '',
    guest_phone:  '',
    booking_type: 'in-person',
    notes:        '',
});

watch(participantMode, () => {
    form.user_id = null;
    form.guest_name = '';
    form.guest_phone = '';
    userSearch.value = '';
    selectedUser.value = null;
    showDropdown.value = false;
});

const submitBooking = () => {
    form.post(`/master/class-schedule/${props.schedule.id}/bookings`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            userSearch.value = '';
            selectedUser.value = null;
        },
    });
};

const removeBooking = (bookingId: number) => {
    if (confirm('Hapus peserta ini dari jadwal?')) {
        router.delete(`/master/class-schedule/${props.schedule.id}/bookings/${bookingId}`, {
            preserveScroll: true,
        });
    }
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Peserta Kelas" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-6">

                <!-- Header -->
                <div class="flex items-center gap-4">
                    <Link href="/master/class-schedule"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-md border bg-background hover:bg-muted transition">
                        <ArrowLeft :size="16" />
                    </Link>
                    <div>
                        <Heading variant="small"
                            :title="`Peserta: ${schedule.gym_class?.name}`"
                            :description="`${formatDate(schedule.date)} · ${schedule.start_time} – ${schedule.end_time} · ${schedule.location || 'Lokasi tidak diatur'}`" />
                    </div>
                </div>

                <!-- Flash message -->
                <div v-if="flash.success" class="rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">
                    {{ flash.success }}
                </div>

                <!-- Schedule info card -->
                <div class="rounded-xl border bg-background p-5 flex flex-wrap gap-4 text-sm">
                    <div>
                        <span class="text-muted-foreground">Trainer:</span>
                        <span class="ml-1 font-medium">{{ schedule.effective_trainer_name || '-' }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Kapasitas:</span>
                        <span class="ml-1 font-medium"
                            :class="schedule.booked_count >= schedule.capacity ? 'text-red-600' : 'text-green-600'">
                            {{ schedule.booked_count }}/{{ schedule.capacity }}
                        </span>
                    </div>
                    <div v-if="schedule.is_recurring">
                        <span class="ml-1 px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-xs font-medium">
                            🔁 Berulang setiap {{ DAY_NAMES[schedule.recurring_day_of_week] }}
                        </span>
                    </div>
                    <div v-if="schedule.is_cancelled">
                        <span class="ml-1 px-2 py-0.5 rounded bg-red-100 text-red-700 text-xs font-medium">Dibatalkan</span>
                    </div>
                </div>

                <div class="grid md:grid-cols-5 gap-6">

                    <!-- Participants list -->
                    <div class="md:col-span-3 rounded-xl border bg-background overflow-hidden">
                        <div class="px-5 py-4 border-b bg-muted/30 flex items-center justify-between">
                            <span class="font-medium text-sm">Daftar Peserta</span>
                            <span class="text-xs text-muted-foreground">{{ bookings.length }} peserta</span>
                        </div>
                        <table class="w-full text-sm">
                            <thead class="bg-muted/20 text-muted-foreground text-xs border-b">
                                <tr>
                                    <th class="px-4 py-3 text-left">#</th>
                                    <th class="px-4 py-3 text-left">Nama</th>
                                    <th class="px-4 py-3 text-left">Tipe</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="(booking, idx) in bookings" :key="booking.id"
                                    class="hover:bg-muted/10 transition-colors">
                                    <td class="px-4 py-3 text-muted-foreground">{{ idx + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ booking.participant }}</div>
                                        <div v-if="booking.user_email" class="text-xs text-muted-foreground">
                                            {{ booking.user_email }}
                                        </div>
                                        <div v-else-if="booking.guest_phone" class="text-xs text-muted-foreground">
                                            {{ booking.guest_phone }}
                                        </div>
                                        <span v-if="!booking.user_id"
                                            class="text-[10px] px-1.5 py-0.5 rounded bg-orange-100 text-orange-600 font-medium">
                                            Tamu
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 capitalize">{{ booking.booking_type }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="{
                                            'bg-green-100 text-green-700': booking.status === 'confirmed',
                                            'bg-blue-100 text-blue-700': booking.status === 'completed',
                                        }" class="px-2 py-0.5 rounded-full text-xs font-medium">
                                            {{ booking.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button @click="removeBooking(booking.id)" type="button"
                                            class="cursor-pointer inline-flex items-center justify-center w-7 h-7 rounded bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition"
                                            title="Hapus peserta">
                                            <Trash2 :size="13" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="bookings.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-muted-foreground text-xs">
                                        Belum ada peserta terdaftar.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Add participant form -->
                    <div class="md:col-span-2 rounded-xl border bg-background p-5 space-y-4 self-start">
                        <div class="flex items-center gap-2 mb-1">
                            <UserPlus :size="16" class="text-primary" />
                            <span class="font-medium text-sm">Tambah Peserta</span>
                        </div>

                        <!-- Mode toggle -->
                        <div class="flex gap-2">
                            <button type="button"
                                @click="participantMode = 'user'"
                                :class="participantMode === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                                class="flex-1 px-3 py-1.5 rounded-md text-xs font-medium transition">
                                Dari Member
                            </button>
                            <button type="button"
                                @click="participantMode = 'guest'"
                                :class="participantMode === 'guest' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                                class="flex-1 px-3 py-1.5 rounded-md text-xs font-medium transition">
                                Input Nama Tamu
                            </button>
                        </div>

                        <form @submit.prevent="submitBooking" class="space-y-3">

                            <!-- User autocomplete -->
                            <template v-if="participantMode === 'user'">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-medium">Cari & Pilih Member <span class="text-destructive">*</span></label>

                                    <!-- Selected user chip -->
                                    <div v-if="selectedUser"
                                        class="flex items-center justify-between gap-2 rounded-md border border-primary/40 bg-primary/5 px-3 py-2 text-sm">
                                        <div class="min-w-0">
                                            <div class="font-medium truncate">{{ selectedUser.name }}</div>
                                            <div class="text-xs text-muted-foreground truncate">{{ selectedUser.email }}</div>
                                        </div>
                                        <button type="button" @click="clearUser"
                                            class="shrink-0 text-muted-foreground hover:text-destructive transition">
                                            <X :size="14" />
                                        </button>
                                    </div>

                                    <!-- Search input + dropdown -->
                                    <div v-else class="relative">
                                        <Input
                                            v-model="userSearch"
                                            placeholder="Ketik nama atau email..."
                                            class="text-sm"
                                            @focus="onSearchFocus"
                                            @blur="onSearchBlur"
                                        />
                                        <div v-if="showDropdown"
                                            class="absolute z-20 mt-1 w-full rounded-md border bg-background shadow-lg max-h-52 overflow-y-auto">
                                            <div v-if="filteredUsers.length === 0"
                                                class="px-3 py-2 text-xs text-muted-foreground">
                                                Tidak ada member ditemukan.
                                            </div>
                                            <button
                                                v-for="u in filteredUsers"
                                                :key="u.id"
                                                type="button"
                                                @mousedown.prevent="selectUser(u)"
                                                class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-muted transition">
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-medium truncate">{{ u.name }}</div>
                                                    <div class="text-xs text-muted-foreground truncate">{{ u.email }}</div>
                                                </div>
                                            </button>
                                        </div>
                                    </div>

                                    <p v-if="form.errors.user_id" class="text-xs text-destructive">{{ form.errors.user_id }}</p>
                                </div>
                            </template>

                            <!-- Guest input -->
                            <template v-else>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-medium">Nama Tamu <span class="text-destructive">*</span></label>
                                    <Input v-model="form.guest_name" placeholder="Nama lengkap..." class="text-sm" />
                                    <p v-if="form.errors.guest_name" class="text-xs text-destructive">{{ form.errors.guest_name }}</p>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-medium">No. HP Tamu (opsional)</label>
                                    <Input v-model="form.guest_phone" placeholder="08xxxxxxxxxx" class="text-sm" />
                                </div>
                            </template>

                            <!-- Booking type -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium">Tipe Booking</label>
                                <select v-model="form.booking_type"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background">
                                    <option value="in-person">In-Person</option>
                                    <option value="online">Online</option>
                                </select>
                            </div>

                            <!-- Notes -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium">Catatan (opsional)</label>
                                <Input v-model="form.notes" placeholder="Catatan tambahan..." class="text-sm" />
                            </div>

                            <button type="submit" :disabled="form.processing"
                                class="w-full cursor-pointer rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:opacity-50 transition">
                                Tambahkan Peserta
                            </button>
                        </form>
                    </div>

                </div>

            </div>
        </div>
    </AppLayout>
</template>
