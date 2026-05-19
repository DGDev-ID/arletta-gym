<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { debounce } from 'lodash';
import { AlertTriangle, CheckCircle2, Clock, Eye, Loader2, ScanLine, XCircle } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { QrcodeStream } from 'vue-qrcode-reader';
import Heading from '@/components/Heading.vue';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

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
    membership_end_at: string | null;
    days_remaining: number | null;
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
    return props.gyms.find(g => g.id === selectedGymId.value);
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
            params: { gym_id: selectedGymId.value, search: memberSearch.value, page },
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
    cameraError.value = error.message || 'Gagal mengakses kamera. Pastikan izin kamera telah diberikan.';
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
    } catch (error: any) {
        if (error.response?.data) {
            scanResult.value = error.response.data;
        } else {
            scanResult.value = {
                success: false,
                message: 'Terjadi kesalahan. Silakan coba lagi.',
            };
        }
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
        case 'active': return 'bg-green-100 text-green-800 border-green-200';
        case 'freeze': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'expired': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-muted text-muted-foreground border-border';
    }
};

const membershipLabel = (status: string) => {
    switch (status) {
        case 'active': return 'Aktif';
        case 'freeze': return 'Freeze';
        case 'expired': return 'Expired';
        default: return status;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Scan QR Code" />
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">
                <Heading variant="small" title="Scan QR Code" description="Scan QR code member untuk verifikasi kehadiran di gym." />

                <!-- Step 1: Select Gym -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Pilih Gym</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <select
                            v-model="selectedGymId"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        >
                            <option :value="null" disabled>-- Pilih Gym --</option>
                            <option v-for="gym in gyms" :key="gym.id" :value="gym.id">
                                {{ gym.name }}
                            </option>
                        </select>
                    </CardContent>
                </Card>

                <!-- Step 2: Scanner -->
                <Card v-if="selectedGymId">
                    <CardHeader>
                        <CardTitle class="text-base flex items-center gap-2">
                            <ScanLine class="h-5 w-5" />
                            Scanner - {{ selectedGym?.name }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Camera Error -->
                        <Alert v-if="cameraError" variant="destructive">
                            <XCircle class="h-4 w-4" />
                            <AlertTitle>Error Kamera</AlertTitle>
                            <AlertDescription>{{ cameraError }}</AlertDescription>
                        </Alert>

                        <!-- QR Scanner View -->
                        <div v-if="scanning" class="relative rounded-lg overflow-hidden border border-border">
                            <QrcodeStream @detect="onDetect" @error="onCameraError">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-56 h-56 border-2 border-primary rounded-2xl opacity-60" />
                                </div>
                            </QrcodeStream>
                        </div>

                        <!-- Scan Actions -->
                        <div class="flex gap-3">
                            <Button v-if="!scanning" @click="startScanning" :disabled="loading" class="gap-2">
                                <ScanLine class="h-4 w-4" />
                                {{ scanResult ? 'Scan Lagi' : 'Mulai Scan' }}
                            </Button>
                            <Button v-if="scanning" variant="outline" @click="stopScanning">
                                Berhenti
                            </Button>
                            <Button v-if="scanResult" variant="outline" @click="resetScan">
                                Reset
                            </Button>
                        </div>

                        <!-- Loading -->
                        <div v-if="loading" class="flex items-center gap-3 text-muted-foreground py-4">
                            <Loader2 class="h-5 w-5 animate-spin" />
                            <span>Memverifikasi member...</span>
                        </div>

                        <!-- Scan Result -->
                        <div v-if="scanResult && !loading">
                            <!-- Success -->
                            <Alert v-if="scanResult.success" class="border-green-500/50 bg-green-500/10 text-green-400">
                                <CheckCircle2 class="h-4 w-4 !text-green-400" />
                                <AlertTitle class="text-green-400">Berhasil</AlertTitle>
                                <AlertDescription class="text-green-400/80">
                                    {{ scanResult.message }}
                                </AlertDescription>
                            </Alert>
                            <div v-if="scanResult.success && scanResult.data" class="mt-4 rounded-lg border border-border bg-card p-4 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Nama</span>
                                    <span class="font-medium">{{ scanResult.data.user_name }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Email</span>
                                    <span class="font-medium">{{ scanResult.data.user_email }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Gym</span>
                                    <span class="font-medium">{{ scanResult.data.gym_name }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Waktu Scan</span>
                                    <span class="font-medium">{{ scanResult.data.scanned_at }}</span>
                                </div>
                            </div>

                            <!-- Membership Reminder -->
                            <Alert v-if="scanResult.success && scanResult.data?.is_reminder" class="mt-4 border-yellow-500/50 bg-yellow-500/10 text-yellow-400">
                                <AlertTriangle class="h-4 w-4 !text-yellow-400" />
                                <AlertTitle class="text-yellow-400">Pengingat Membership</AlertTitle>
                                <AlertDescription class="text-yellow-400/80">
                                    Sisa membership Anda tinggal {{ scanResult.data.reminder_day }} hari.
                                </AlertDescription>
                            </Alert>

                            <!-- Activity Log History -->
                            <div v-if="scanResult.success && scanResult.data?.activity_logs?.length" class="mt-4">
                                <h4 class="text-sm font-medium mb-2 flex items-center gap-2">
                                    <Clock class="h-4 w-4" />
                                    Riwayat Scan Terakhir
                                </h4>
                                <div class="rounded-lg border border-border bg-card divide-y divide-border">
                                    <div v-for="(log, index) in scanResult.data.activity_logs" :key="index" class="px-4 py-2 flex justify-between text-sm">
                                        <span class="text-muted-foreground">{{ log.description }}</span>
                                        <span class="font-medium text-xs">{{ log.logged_at }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Error -->
                            <Alert v-if="!scanResult.success" variant="destructive">
                                <XCircle class="h-4 w-4" />
                                <AlertTitle>Gagal</AlertTitle>
                                <AlertDescription>{{ scanResult.message }}</AlertDescription>
                            </Alert>
                        </div>
                    </CardContent>
                </Card>

                <!-- Placeholder when no gym selected -->
                <Card v-else class="border-dashed">
                    <CardContent class="flex flex-col items-center justify-center py-12 text-muted-foreground space-y-3">
                        <ScanLine class="h-12 w-12 opacity-40" />
                        <p class="text-sm">Pilih gym terlebih dahulu untuk mulai scan.</p>
                    </CardContent>
                </Card>

                <!-- Step 3: Member Table -->
                <Card v-if="selectedGymId">
                    <CardHeader>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <CardTitle class="text-base">Data Member</CardTitle>
                            <div class="w-full sm:w-64">
                                <Input v-model="memberSearch" placeholder="Cari username atau email..." class="rounded-xl" />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div v-if="loadingMembers" class="flex items-center gap-3 text-muted-foreground py-6 px-6">
                            <Loader2 class="h-5 w-5 animate-spin" />
                            <span>Memuat data member...</span>
                        </div>
                        <div v-else class="rounded-xl border bg-background overflow-hidden">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-muted/50 text-muted-foreground font-medium border-b">
                                    <tr>
                                        <th class="px-6 py-4">No</th>
                                        <th class="px-6 py-4">Email</th>
                                        <th class="px-6 py-4">Username</th>
                                        <th class="px-6 py-4">Role</th>
                                        <th class="px-6 py-4">Status Membership</th>
                                        <th class="px-6 py-4">Membership Selesai</th>
                                        <th class="px-6 py-4">Sisa Hari</th>
                                        <th class="px-6 py-4">
                                            <div class="flex justify-center items-center">Aksi</div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="(member, index) in memberRows" :key="member.user_id" class="hover:bg-muted/20 transition-colors">
                                        <td class="px-6 py-4">{{ (memberPagination.current_page - 1) * memberPagination.per_page + index + 1 }}</td>
                                        <td class="px-6 py-4">{{ member.user_email }}</td>
                                        <td class="px-6 py-4 font-medium">{{ member.user_name }}</td>
                                        <td class="px-6 py-4">{{ member.user_role }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 inline-flex text-[10px] uppercase tracking-wider font-bold rounded-full border" :class="membershipBadgeClass(member.membership_status)">
                                                {{ membershipLabel(member.membership_status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ member.membership_end_at || '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="member.days_remaining !== null" class="font-medium" :class="member.days_remaining <= 0 ? 'text-red-500' : member.days_remaining <= 7 ? 'text-yellow-500' : 'text-green-600'">
                                                {{ member.days_remaining <= 0 ? 'Habis' : member.days_remaining + ' hari' }}
                                            </span>
                                            <span v-else class="text-muted-foreground">-</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex justify-center items-center">
                                                <button type="button" @click="openDetail(member.user_id)" class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition">
                                                    <Eye :size="16" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!memberRows.length">
                                        <td colspan="8" class="px-6 py-10 text-center text-muted-foreground">
                                            {{ memberSearch ? 'Tidak ada member yang cocok dengan pencarian.' : 'Belum ada data member.' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="memberPagination.last_page > 1" class="px-6 py-4 flex justify-end">
                            <nav class="inline-flex rounded-md shadow-sm -space-x-px">
                                <button
                                    :disabled="memberPagination.current_page === 1"
                                    @click="fetchMembers(memberPagination.current_page - 1)"
                                    class="px-3 py-2 text-sm border border-gray-300 rounded-l-md"
                                    :class="memberPagination.current_page === 1 ? 'text-gray-400 bg-gray-100 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100 cursor-pointer'"
                                >‹</button>
                                <template v-for="p in memberPagination.last_page" :key="p">
                                    <button
                                        @click="fetchMembers(p)"
                                        class="px-3 py-2 text-sm border border-gray-300 cursor-pointer"
                                        :class="p === memberPagination.current_page ? 'z-10 bg-primary border-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                                    >{{ p }}</button>
                                </template>
                                <button
                                    :disabled="memberPagination.current_page === memberPagination.last_page"
                                    @click="fetchMembers(memberPagination.current_page + 1)"
                                    class="px-3 py-2 text-sm border border-gray-300 rounded-r-md"
                                    :class="memberPagination.current_page === memberPagination.last_page ? 'text-gray-400 bg-gray-100 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100 cursor-pointer'"
                                >›</button>
                            </nav>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Detail Dialog -->
        <Dialog v-model:open="showDetail">
            <DialogContent class="max-w-2xl max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Detail Member</DialogTitle>
                    <DialogDescription>Informasi member dan riwayat log aktivitas scan.</DialogDescription>
                </DialogHeader>

                <div v-if="loadingDetail" class="flex items-center gap-3 text-muted-foreground py-8 justify-center">
                    <Loader2 class="h-5 w-5 animate-spin" />
                    <span>Memuat detail member...</span>
                </div>

                <div v-else-if="memberDetail" class="space-y-6">
                    <!-- User Info -->
                    <div class="rounded-lg border border-border bg-card p-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Nama</span>
                            <span class="font-medium">{{ memberDetail.user_name }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Email</span>
                            <span class="font-medium">{{ memberDetail.user_email }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Role</span>
                            <span class="font-medium">{{ memberDetail.user_role }}</span>
                        </div>
                        <div class="flex justify-between text-sm items-center">
                            <span class="text-muted-foreground">Status Membership</span>
                            <span class="px-2 py-1 inline-flex text-[10px] uppercase tracking-wider font-bold rounded-full border" :class="membershipBadgeClass(memberDetail.membership_status)">
                                {{ membershipLabel(memberDetail.membership_status) }}
                            </span>
                        </div>
                        <div v-if="memberDetail.membership_end_at" class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Membership Berakhir</span>
                            <span class="font-medium">{{ memberDetail.membership_end_at }}</span>
                        </div>
                    </div>

                    <!-- Activity Logs -->
                    <div>
                        <h4 class="text-sm font-medium mb-3 flex items-center gap-2">
                            <Clock class="h-4 w-4" />
                            Riwayat Log Activity
                        </h4>
                        <div class="rounded-xl border bg-background overflow-hidden">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-muted/50 text-muted-foreground font-medium border-b">
                                    <tr>
                                        <th class="px-6 py-4">No</th>
                                        <th class="px-6 py-4">Deskripsi</th>
                                        <th class="px-6 py-4">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="(log, index) in memberDetail.activity_logs" :key="index" class="hover:bg-muted/20 transition-colors">
                                        <td class="px-6 py-4">{{ index + 1 }}</td>
                                        <td class="px-6 py-4">{{ log.description }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ log.logged_at }}</td>
                                    </tr>
                                    <tr v-if="!memberDetail.activity_logs.length">
                                        <td colspan="3" class="px-6 py-10 text-center text-muted-foreground">
                                            Belum ada riwayat aktivitas.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center text-sm text-muted-foreground py-8">
                    Gagal memuat detail member.
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
