<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { debounce } from 'lodash';
import {
    AlertTriangle,
    CheckCircle2,
    Clock,
    Eye,
    Loader2,
    ScanLine,
    XCircle,
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { CheckCircle2, Loader2, ScanLine, XCircle } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { QrcodeStream } from 'vue-qrcode-reader';
import Heading from '@/components/Heading.vue';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

// ── Audio (sama seperti arletta-cafe) ─────────────────────────────────────
let audioCtx: AudioContext | null = null;
const userHasInteracted = ref(false);

const unlockAudio = () => {
    userHasInteracted.value = true;
    if (!audioCtx) audioCtx = new AudioContext();
    if (audioCtx.state === 'suspended') audioCtx.resume();
    document.removeEventListener('click', unlockAudio);
    document.removeEventListener('touchstart', unlockAudio);
};

const speak = (text: string) => {
    if (!userHasInteracted.value) return;
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'id-ID';
        utterance.rate = 1;
        window.speechSynthesis.speak(utterance);
    }
};

/** Beep sukses — nada naik 700 → 900 Hz (pleasant) */
const playSuccessBeep = () => {
    if (!audioCtx || audioCtx.state !== 'running') return;
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.frequency.value = 700;
    osc.type = 'sine';
    gain.gain.value = 0.3;
    osc.start();
    osc.stop(audioCtx.currentTime + 0.25);
    setTimeout(() => {
        const osc2 = audioCtx!.createOscillator();
        const gain2 = audioCtx!.createGain();
        osc2.connect(gain2);
        gain2.connect(audioCtx!.destination);
        osc2.frequency.value = 900;
        osc2.type = 'sine';
        gain2.gain.value = 0.3;
        osc2.start();
        osc2.stop(audioCtx!.currentTime + 0.25);
    }, 300);
};

/** Beep gagal — nada turun 400 → 250 Hz (warning) */
const playErrorBeep = () => {
    if (!audioCtx || audioCtx.state !== 'running') return;
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.frequency.value = 400;
    osc.type = 'square';
    gain.gain.value = 0.25;
    osc.start();
    osc.stop(audioCtx.currentTime + 0.2);
    setTimeout(() => {
        const osc2 = audioCtx!.createOscillator();
        const gain2 = audioCtx!.createGain();
        osc2.connect(gain2);
        gain2.connect(audioCtx!.destination);
        osc2.frequency.value = 250;
        osc2.type = 'square';
        gain2.gain.value = 0.25;
        osc2.start();
        osc2.stop(audioCtx!.currentTime + 0.35);
    }, 250);
};

interface Gym {
    id: number;
    name: string;
}

interface ActivityLog {
    activity: string;
    description: string;
    logged_at: string;
}

interface MemberPagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface MemberRow {
    user_id: number;
    user_name: string;
    user_email: string;
    user_role: string;
    membership_status: string;
    scanned_at: string;
}

interface MemberDetail {
    user_id: number;
    user_name: string;
    user_email: string;
    user_role: string;
    membership_status: string;
    membership_end_at: string | null;
    activity_logs: ActivityLog[];
}

interface ScanResult {
    success: boolean;
    message: string;
    data?: {
        user_id: number;
        user_name: string;
        user_email: string;
        user_role: string;
        membership_status: string;
        gym_name: string;
        scanned_at: string;
        is_reminder: boolean;
        reminder_day: number | null;
        activity_logs: ActivityLog[];
    };
}

const props = defineProps<{
    gyms: Gym[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Scan QR Code', href: '/scan-qr' },
];

const selectedGymId = ref<number | null>(null);
const scanning = ref(false);
const loading = ref(false);
const scanResult = ref<ScanResult | null>(null);
const cameraError = ref<string | null>(null);

// Member table state
const memberRows = ref<MemberRow[]>([]);
const loadingMembers = ref(false);
const memberSearch = ref('');
const memberPagination = ref<MemberPagination>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});

// Detail dialog state
const showDetail = ref(false);
const loadingDetail = ref(false);
const memberDetail = ref<MemberDetail | null>(null);

const selectedGym = computed(() => {
    return props.gyms.find((g) => g.id === selectedGymId.value);
});

// Fetch today's scanned members when gym changes
const fetchMembers = async (page = 1) => {
    if (!selectedGymId.value) {
        memberRows.value = [];
        return;
    }
    loadingMembers.value = true;
    try {
        const response = await axios.get('/scan-qr/members', {
            params: {
                gym_id: selectedGymId.value,
                search: memberSearch.value,
                page,
            },
        });
        if (response.data.success) {
            memberRows.value = response.data.data;
            memberPagination.value = response.data.pagination;
        }
    } catch {
        memberRows.value = [];
    } finally {
        loadingMembers.value = false;
    }
};

watch(selectedGymId, () => {
    memberSearch.value = '';
    fetchMembers(1);
});

const debouncedSearch = debounce(() => fetchMembers(1), 300);
watch(memberSearch, debouncedSearch);

const startScanning = () => {
    scanResult.value = null;
    cameraError.value = null;
    scanning.value = true;
};

const stopScanning = () => {
    scanning.value = false;
};

const onCameraError = (error: Error) => {
    cameraError.value =
        error.message ||
        'Gagal mengakses kamera. Pastikan izin kamera telah diberikan.';
    scanning.value = false;
};

const onDetect = async (detectedCodes: any[]) => {
    if (loading.value || !detectedCodes.length) return;

    const rawValue = detectedCodes[0].rawValue;
    if (!rawValue) return;

    // Stop scanning and process
    scanning.value = false;
    loading.value = true;
    scanResult.value = null;

    try {
        const response = await axios.post('/scan-qr', {
            unique_id: rawValue,
            gym_id: selectedGymId.value,
        });
        scanResult.value = response.data;

        // Update member table after successful scan
        if (response.data.success) {
            await fetchMembers(memberPagination.value.current_page);
        }
        // Play audio feedback
        if (scanResult.value?.success) {
            playSuccessBeep();
            speak(
                'Verifikasi berhasil. Selamat datang di Arletta Gym, Silakan masuk.',
            );
        } else {
            playErrorBeep();
            speak(
                'Verifikasi gagal. Silakan coba kembali atau Hubungi petugas gym.',
            );
        }
    } catch (error: any) {
        if (error.response?.data) {
            scanResult.value = error.response.data;
        } else {
            scanResult.value = {
                success: false,
                message: 'Terjadi kesalahan. Silakan coba lagi.',
            };
        }
        // Play error beep + speak on failure
        playErrorBeep();
        speak(
            'Verifikasi gagal. Silakan coba kembali atau Hubungi petugas gym.',
        );
    } finally {
        loading.value = false;
    }
};

const resetScan = () => {
    scanResult.value = null;
    scanning.value = false;
    loading.value = false;
    cameraError.value = null;
};

// Detail dialog
const openDetail = async (userId: number) => {
    showDetail.value = true;
    loadingDetail.value = true;
    memberDetail.value = null;
    try {
        const response = await axios.get(`/scan-qr/member/${userId}`, {
            params: { gym_id: selectedGymId.value },
        });
        if (response.data.success) {
            memberDetail.value = response.data.data;
        }
    } catch {
        memberDetail.value = null;
    } finally {
        loadingDetail.value = false;
    }
};

const membershipBadgeClass = (status: string) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'freeze':
            return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'expired':
            return 'bg-red-100 text-red-800 border-red-200';
        default:
            return 'bg-muted text-muted-foreground border-border';
    }
};

const membershipLabel = (status: string) => {
    switch (status) {
        case 'active':
            return 'Aktif';
        case 'freeze':
            return 'Freeze';
        case 'expired':
            return 'Expired';
        default:
            return status;
    }
};

onMounted(() => {
    document.addEventListener('click', unlockAudio);
    document.addEventListener('touchstart', unlockAudio);
});

onUnmounted(() => {
    document.removeEventListener('click', unlockAudio);
    document.removeEventListener('touchstart', unlockAudio);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Scan QR Code" />
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="mx-auto max-w-7xl space-y-8 px-6">
                <Heading
                    variant="small"
                    title="Scan QR Code"
                    description="Scan QR code member untuk verifikasi kehadiran di gym."
                />

                <!-- Audio unlock banner -->
                <div
                    v-if="!userHasInteracted"
                    class="flex cursor-pointer items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 select-none"
                    @click="unlockAudio"
                >
                    <span class="text-lg">🔔</span>
                    <span
                        >Klik di sini untuk mengaktifkan notifikasi audio saat
                        scan QR.</span
                    >
                </div>
                <div
                    v-else
                    class="flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-700"
                >
                    <span class="text-base">🔊</span>
                    <span
                        >Notifikasi audio aktif — akan berbunyi saat scan
                        berhasil atau gagal.</span
                    >
                </div>

                <!-- Step 1: Select Gym -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Pilih Gym</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <select
                            v-model="selectedGymId"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                        >
                            <option :value="null" disabled>
                                -- Pilih Gym --
                            </option>
                            <option
                                v-for="gym in gyms"
                                :key="gym.id"
                                :value="gym.id"
                            >
                                {{ gym.name }}
                            </option>
                        </select>
                    </CardContent>
                </Card>

                <!-- Step 2: Scanner -->
                <Card v-if="selectedGymId">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <ScanLine class="h-5 w-5" />
                            Scanner - {{ selectedGym?.name }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Camera Error -->
                        <Alert v-if="cameraError" variant="destructive">
                            <XCircle class="h-4 w-4" />
                            <AlertTitle>Error Kamera</AlertTitle>
                            <AlertDescription>{{
                                cameraError
                            }}</AlertDescription>
                        </Alert>

                        <!-- QR Scanner View -->
                        <div
                            v-if="scanning"
                            class="relative overflow-hidden rounded-lg border border-border"
                        >
                            <QrcodeStream
                                @detect="onDetect"
                                @error="onCameraError"
                            >
                                <div
                                    class="absolute inset-0 flex items-center justify-center"
                                >
                                    <div
                                        class="h-56 w-56 rounded-2xl border-2 border-primary opacity-60"
                                    />
                                </div>
                            </QrcodeStream>
                        </div>

                        <!-- Scan Actions -->
                        <div class="flex gap-3">
                            <Button
                                v-if="!scanning"
                                @click="startScanning"
                                :disabled="loading"
                                class="gap-2"
                            >
                                <ScanLine class="h-4 w-4" />
                                {{ scanResult ? 'Scan Lagi' : 'Mulai Scan' }}
                            </Button>
                            <Button
                                v-if="scanning"
                                variant="outline"
                                @click="stopScanning"
                            >
                                Berhenti
                            </Button>
                            <Button
                                v-if="scanResult"
                                variant="outline"
                                @click="resetScan"
                            >
                                Reset
                            </Button>
                        </div>

                        <!-- Loading -->
                        <div
                            v-if="loading"
                            class="flex items-center gap-3 py-4 text-muted-foreground"
                        >
                            <Loader2 class="h-5 w-5 animate-spin" />
                            <span>Memverifikasi member...</span>
                        </div>

                        <!-- Scan Result -->
                        <div v-if="scanResult && !loading">
                            <!-- Success -->
                            <Alert
                                v-if="scanResult.success"
                                class="border-green-500/50 bg-green-500/10 text-green-400"
                            >
                                <CheckCircle2 class="h-4 w-4 !text-green-400" />
                                <AlertTitle class="text-green-400"
                                    >Berhasil</AlertTitle
                                >
                                <AlertDescription class="text-green-400/80">
                                    {{ scanResult.message }}
                                </AlertDescription>
                            </Alert>
                            <div
                                v-if="scanResult.success && scanResult.data"
                                class="mt-4 space-y-2 rounded-lg border border-border bg-card p-4"
                            >
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground"
                                        >Nama</span
                                    >
                                    <span class="font-medium">{{
                                        scanResult.data.user_name
                                    }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground"
                                        >Email</span
                                    >
                                    <span class="font-medium">{{
                                        scanResult.data.user_email
                                    }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground"
                                        >Gym</span
                                    >
                                    <span class="font-medium">{{
                                        scanResult.data.gym_name
                                    }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground"
                                        >Waktu Scan</span
                                    >
                                    <span class="font-medium">{{
                                        scanResult.data.scanned_at
                                    }}</span>
                                </div>
                            </div>

                            <!-- Membership Reminder -->
                            <Alert
                                v-if="
                                    scanResult.success &&
                                    scanResult.data?.is_reminder
                                "
                                class="mt-4 border-yellow-500/50 bg-yellow-500/10 text-yellow-400"
                            >
                                <AlertTriangle
                                    class="h-4 w-4 !text-yellow-400"
                                />
                                <AlertTitle class="text-yellow-400"
                                    >Pengingat Membership</AlertTitle
                                >
                                <AlertDescription class="text-yellow-400/80">
                                    Sisa membership Anda tinggal
                                    {{ scanResult.data.reminder_day }} hari.
                                </AlertDescription>
                            </Alert>

                            <!-- Activity Log History -->
                            <div
                                v-if="
                                    scanResult.success &&
                                    scanResult.data?.activity_logs?.length
                                "
                                class="mt-4"
                            >
                                <h4
                                    class="mb-2 flex items-center gap-2 text-sm font-medium"
                                >
                                    <Clock class="h-4 w-4" />
                                    Riwayat Scan Terakhir
                                </h4>
                                <div
                                    class="divide-y divide-border rounded-lg border border-border bg-card"
                                >
                                    <div
                                        v-for="(log, index) in scanResult.data
                                            .activity_logs"
                                        :key="index"
                                        class="flex justify-between px-4 py-2 text-sm"
                                    >
                                        <span class="text-muted-foreground">{{
                                            log.description
                                        }}</span>
                                        <span class="text-xs font-medium">{{
                                            log.logged_at
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Error -->
                            <Alert
                                v-if="!scanResult.success"
                                variant="destructive"
                            >
                                <XCircle class="h-4 w-4" />
                                <AlertTitle>Gagal</AlertTitle>
                                <AlertDescription>{{
                                    scanResult.message
                                }}</AlertDescription>
                            </Alert>
                        </div>
                    </CardContent>
                </Card>

                <!-- Placeholder when no gym selected -->
                <Card v-else class="border-dashed">
                    <CardContent
                        class="flex flex-col items-center justify-center space-y-3 py-12 text-muted-foreground"
                    >
                        <ScanLine class="h-12 w-12 opacity-40" />
                        <p class="text-sm">
                            Pilih gym terlebih dahulu untuk mulai scan.
                        </p>
                    </CardContent>
                </Card>

                <!-- Step 3: Member Table -->
                <Card v-if="selectedGymId">
                    <CardHeader>
                        <div
                            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
                        >
                            <CardTitle class="text-base">Data Member</CardTitle>
                            <div class="w-full sm:w-64">
                                <Input
                                    v-model="memberSearch"
                                    placeholder="Cari username atau email..."
                                    class="rounded-xl"
                                />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div
                            v-if="loadingMembers"
                            class="flex items-center gap-3 px-6 py-6 text-muted-foreground"
                        >
                            <Loader2 class="h-5 w-5 animate-spin" />
                            <span>Memuat data member...</span>
                        </div>
                        <div
                            v-else
                            class="overflow-hidden rounded-xl border bg-background"
                        >
                            <table class="w-full text-left text-sm">
                                <thead
                                    class="border-b bg-muted/50 font-medium text-muted-foreground"
                                >
                                    <tr>
                                        <th class="px-6 py-4">No</th>
                                        <th class="px-6 py-4">Email</th>
                                        <th class="px-6 py-4">Username</th>
                                        <th class="px-6 py-4">Role</th>
                                        <th class="px-6 py-4">
                                            Status Membership
                                        </th>
                                        <th class="px-6 py-4">
                                            <div
                                                class="flex items-center justify-center"
                                            >
                                                Aksi
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="(member, index) in memberRows"
                                        :key="member.user_id"
                                        class="transition-colors hover:bg-muted/20"
                                    >
                                        <td class="px-6 py-4">
                                            {{
                                                (memberPagination.current_page -
                                                    1) *
                                                    memberPagination.per_page +
                                                index +
                                                1
                                            }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ member.user_email }}
                                        </td>
                                        <td class="px-6 py-4 font-medium">
                                            {{ member.user_name }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ member.user_role }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="inline-flex rounded-full border px-2 py-1 text-[10px] font-bold tracking-wider uppercase"
                                                :class="
                                                    membershipBadgeClass(
                                                        member.membership_status,
                                                    )
                                                "
                                            >
                                                {{
                                                    membershipLabel(
                                                        member.membership_status,
                                                    )
                                                }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div
                                                class="flex items-center justify-center"
                                            >
                                                <button
                                                    type="button"
                                                    @click="
                                                        openDetail(
                                                            member.user_id,
                                                        )
                                                    "
                                                    class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-md bg-blue-100 text-blue-600 transition hover:bg-blue-600 hover:text-white"
                                                >
                                                    <Eye :size="16" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!memberRows.length">
                                        <td
                                            colspan="6"
                                            class="px-6 py-10 text-center text-muted-foreground"
                                        >
                                            {{
                                                memberSearch
                                                    ? 'Tidak ada member yang cocok dengan pencarian.'
                                                    : 'Belum ada data member.'
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div
                            v-if="memberPagination.last_page > 1"
                            class="flex justify-end px-6 py-4"
                        >
                            <nav
                                class="inline-flex -space-x-px rounded-md shadow-sm"
                            >
                                <button
                                    :disabled="
                                        memberPagination.current_page === 1
                                    "
                                    @click="
                                        fetchMembers(
                                            memberPagination.current_page - 1,
                                        )
                                    "
                                    class="rounded-l-md border border-gray-300 px-3 py-2 text-sm"
                                    :class="
                                        memberPagination.current_page === 1
                                            ? 'cursor-not-allowed bg-gray-100 text-gray-400'
                                            : 'cursor-pointer bg-white text-gray-700 hover:bg-gray-100'
                                    "
                                >
                                    ‹
                                </button>
                                <template
                                    v-for="p in memberPagination.last_page"
                                    :key="p"
                                >
                                    <button
                                        @click="fetchMembers(p)"
                                        class="cursor-pointer border border-gray-300 px-3 py-2 text-sm"
                                        :class="
                                            p === memberPagination.current_page
                                                ? 'z-10 border-primary bg-primary text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-100'
                                        "
                                    >
                                        {{ p }}
                                    </button>
                                </template>
                                <button
                                    :disabled="
                                        memberPagination.current_page ===
                                        memberPagination.last_page
                                    "
                                    @click="
                                        fetchMembers(
                                            memberPagination.current_page + 1,
                                        )
                                    "
                                    class="rounded-r-md border border-gray-300 px-3 py-2 text-sm"
                                    :class="
                                        memberPagination.current_page ===
                                        memberPagination.last_page
                                            ? 'cursor-not-allowed bg-gray-100 text-gray-400'
                                            : 'cursor-pointer bg-white text-gray-700 hover:bg-gray-100'
                                    "
                                >
                                    ›
                                </button>
                            </nav>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Detail Dialog -->
        <Dialog v-model:open="showDetail">
            <DialogContent class="max-h-[85vh] max-w-2xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Detail Member</DialogTitle>
                    <DialogDescription
                        >Informasi member dan riwayat log aktivitas
                        scan.</DialogDescription
                    >
                </DialogHeader>

                <div
                    v-if="loadingDetail"
                    class="flex items-center justify-center gap-3 py-8 text-muted-foreground"
                >
                    <Loader2 class="h-5 w-5 animate-spin" />
                    <span>Memuat detail member...</span>
                </div>

                <div v-else-if="memberDetail" class="space-y-6">
                    <!-- User Info -->
                    <div
                        class="space-y-2 rounded-lg border border-border bg-card p-4"
                    >
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Nama</span>
                            <span class="font-medium">{{
                                memberDetail.user_name
                            }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Email</span>
                            <span class="font-medium">{{
                                memberDetail.user_email
                            }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Role</span>
                            <span class="font-medium">{{
                                memberDetail.user_role
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground"
                                >Status Membership</span
                            >
                            <span
                                class="inline-flex rounded-full border px-2 py-1 text-[10px] font-bold tracking-wider uppercase"
                                :class="
                                    membershipBadgeClass(
                                        memberDetail.membership_status,
                                    )
                                "
                            >
                                {{
                                    membershipLabel(
                                        memberDetail.membership_status,
                                    )
                                }}
                            </span>
                        </div>
                        <div
                            v-if="memberDetail.membership_end_at"
                            class="flex justify-between text-sm"
                        >
                            <span class="text-muted-foreground"
                                >Membership Berakhir</span
                            >
                            <span class="font-medium">{{
                                memberDetail.membership_end_at
                            }}</span>
                        </div>
                    </div>

                    <!-- Activity Logs -->
                    <div>
                        <h4
                            class="mb-3 flex items-center gap-2 text-sm font-medium"
                        >
                            <Clock class="h-4 w-4" />
                            Riwayat Log Activity
                        </h4>
                        <div
                            class="overflow-hidden rounded-xl border bg-background"
                        >
                            <table class="w-full text-left text-sm">
                                <thead
                                    class="border-b bg-muted/50 font-medium text-muted-foreground"
                                >
                                    <tr>
                                        <th class="px-6 py-4">No</th>
                                        <th class="px-6 py-4">Deskripsi</th>
                                        <th class="px-6 py-4">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="(
                                            log, index
                                        ) in memberDetail.activity_logs"
                                        :key="index"
                                        class="transition-colors hover:bg-muted/20"
                                    >
                                        <td class="px-6 py-4">
                                            {{ index + 1 }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ log.description }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ log.logged_at }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            !memberDetail.activity_logs.length
                                        "
                                    >
                                        <td
                                            colspan="3"
                                            class="px-6 py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada riwayat aktivitas.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    Gagal memuat detail member.
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
