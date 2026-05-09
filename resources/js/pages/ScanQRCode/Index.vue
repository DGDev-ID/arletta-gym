<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { CheckCircle2, Loader2, ScanLine, XCircle } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { QrcodeStream } from 'vue-qrcode-reader';
import Heading from '@/components/Heading.vue';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
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

interface ScanResult {
    success: boolean;
    message: string;
    data?: {
        user_name: string;
        user_email: string;
        gym_name: string;
        scanned_at: string;
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

const selectedGym = computed(() => {
    return props.gyms.find(g => g.id === selectedGymId.value);
});

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
        // Play audio feedback
        if (scanResult.value?.success) {
            playSuccessBeep();
            speak('Verifikasi berhasil. Selamat datang di Arletta Gym, Silakan masuk.');
        } else {
            playErrorBeep();
            speak('Verifikasi gagal. Silakan coba kembali atau Hubungi petugas gym.');
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
        speak('Verifikasi gagal. Silakan coba kembali atau Hubungi petugas gym.');
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
            <div class="max-w-3xl mx-auto px-6 space-y-8">
                <Heading variant="small" title="Scan QR Code" description="Scan QR code member untuk verifikasi kehadiran di gym." />

                <!-- Audio unlock banner -->
                <div v-if="!userHasInteracted"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm cursor-pointer select-none"
                    @click="unlockAudio">
                    <span class="text-lg">🔔</span>
                    <span>Klik di sini untuk mengaktifkan notifikasi audio saat scan QR.</span>
                </div>
                <div v-else class="flex items-center gap-2 px-4 py-2 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
                    <span class="text-base">🔊</span>
                    <span>Notifikasi audio aktif — akan berbunyi saat scan berhasil atau gagal.</span>
                </div>

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
            </div>
        </div>
    </AppLayout>
</template>
