<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Save, Lock, User as UserIcon, MapPin,
    Phone, Calendar, Loader2,
    Download, CreditCard, Ticket, Dumbbell, Store, CheckCircle, XCircle, Clock, History, Heart, ImagePlus, Camera, X, Snowflake, ThermometerSun
} from 'lucide-vue-next';
import { ref, computed, watch, onUnmounted } from 'vue';
import Heading from '@/components/Heading.vue';
import Input from "@/components/ui/input/Input.vue";
import Textarea from "@/components/ui/textarea/Textarea.vue";
import AppLayout from '@/layouts/AppLayout.vue';
import QrcodeVue from 'qrcode.vue'
import { router } from '@inertiajs/vue3';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import { formatRupiah } from '@/helpers/formatRupiah';

const notyf = new Notyf({
    duration: 3000,
    position: { x: 'right', y: 'bottom' },
});


interface Gym {
    id: string | number;
    name: string;
    [key: string]: any;
}

const props = defineProps<{
    user: any,
    gyms: Gym[],
    pendingTransactions: any[],
    pendingInstallments: any[],
    pendingFreezings: any[],
    userGyms: any[],
}>();

const installmentPaymentMethods = ref<Record<number, 'manual' | 'va' | 'qris'>>({});
const availableMethods = ['manual', 'va', 'qris'] as const;
const calculateInstallmentTotal = (installment: any) => {
    const base = parseFloat(installment.price);

    // Fee dihapus: VA dan QRIS tidak lagi dikenakan biaya layanan payment gateway
    const fee = 0;

    return {
        base: base,
        fee: fee,
        total: base + fee
    };
};

const handlePayInstallment = async (installment: any) => {
    const method = installmentPaymentMethods.value[installment.id];
    if (!method) {
        notyf.error("Silakan pilih metode pembayaran.");
        return;
    }

    isGenerating.value = true;
    try {
        const payload = {
            user_id: props.user.id,
            payment_method: method,
            user_pt_package_installment_id: installment.id,
        };

        await axios.post('/management/user/generate-installment', payload);

        // Semua metode (manual, VA, QRIS) kini menggunakan alur yang sama:
        // transaksi dibuat dengan status pending dan perlu validasi admin.
        const methodLabel = method === 'va' ? 'Virtual Account' : method === 'qris' ? 'QRIS' : 'Manual';
        notyf.success(`Invoice ${methodLabel} berhasil dibuat! Menunggu validasi admin.`);
        router.reload({ only: ['pendingTransactions', 'pendingInstallments'] });
    } catch (error: any) {
        console.log(error);
        alert("Gagal memproses cicilan: " + (error.response?.data?.message || 'Error'));
    } finally {
        isGenerating.value = false;
    }
};

interface Membership {
    id: string | number;
    [key: string]: any;
}
interface PtPackage {
    id: string | number;
    [key: string]: any;
}
interface GymData {
    memberships: Membership[];
    pt_packages: PtPackage[];
}

interface PaymentForm {
    gym_id: string;
    transaction_type: 'membership' | 'pt' | '';
    selected_item_id: string | number;
    payment_type: string;
    payment_mode: 'full_payment' | 'dp_payment';
    dp_percent: number;
    promo_code: string;
}

const paymentForm = ref<PaymentForm>({
    gym_id: '',
    transaction_type: '',
    selected_item_id: '',
    payment_type: 'manual',
    payment_mode: 'full_payment',
    dp_percent: 20,
    promo_code: '',
});

const gymData = ref<GymData>({ memberships: [], pt_packages: [] });
const appliedPromo = ref(null);
const isLoadingGym = ref(false);
const appliedManualPromo = ref<any>(null);
const isGenerating = ref(false);

watch(() => paymentForm.value.gym_id, async (newGymId) => {
    if (!newGymId) return;
    isLoadingGym.value = true;
    try {
        const res = await axios.get(`/management/user/gym-details/${newGymId}`);
        gymData.value = res.data;
    } finally {
        isLoadingGym.value = false;
    }
});

const approveTransaction = (id: number | string) => {
    if (confirm('Apakah Anda yakin ingin menyetujui pembayaran manual ini?')) {
        router.post(`/management/user/transactions/${id}/approve`);
    }
};

const selectedItem = computed(() => {
    if (paymentForm.value.transaction_type === 'membership') {
        return gymData.value.memberships.find(m => m.id === paymentForm.value.selected_item_id);
    }
    return gymData.value.pt_packages.find(p => p.id === paymentForm.value.selected_item_id);
});

// Selected user's gym record for the chosen gym
const selectedUserGym = computed(() => {
    if (!paymentForm.value.gym_id) return null;
    return props.userGyms.find((ug: any) => String(ug.gym_id) === String(paymentForm.value.gym_id));
});

// Helpers untuk Tanggal
const toISODate = (d: Date | null) => {
    if (!d) return null;
    const date = new Date(d);
    // Mengamankan masalah timezone browser agar format YYYY-MM-DD selalu tepat
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().split('T')[0];
};

const hasActiveMembershipForSelectedGym = computed(() => {
    const ug = selectedUserGym.value;
    if (!ug || !ug.membership_end_at) return false;
    return new Date(ug.membership_end_at) > new Date();
});

// LOGIKA BARU: Tentukan apakah input Tanggal Mulai perlu di-render
const showMembershipDateInput = computed(() => {
    if (paymentForm.value.transaction_type !== 'membership') return false;

    const ug = selectedUserGym.value;
    // Tampilkan jika User baru / belum punya record membership sama sekali
    if (!ug || !ug.membership_end_at) return true;

    const endDate = new Date(ug.membership_end_at);
    const today = new Date();

    // Set waktu jam/menit ke 00:00 untuk komparasi tanggal yang murni
    endDate.setHours(0, 0, 0, 0);
    today.setHours(0, 0, 0, 0);

    // Tampilkan jika Membership berakhir HARI INI atau SUDAH LEWAT
    if (endDate <= today) return true;

    // Sembunyikan opsi jika Membership masih aktif (dan bukan hari ini selesainya)
    return false;
});

// State untuk input Tanggal Mulai
const membershipStartInput = ref<string | null>(toISODate(new Date()));

// Watcher untuk sinkronisasi kapan paket atau gym berubah
watch([() => paymentForm.value.gym_id, () => selectedItem.value], () => {
    if (showMembershipDateInput.value) {
        // Jika UI input ditampilkan (user baru / berakhir hari ini), set default ke hari ini
        membershipStartInput.value = toISODate(new Date());
    } else {
        // Jika UI disembunyikan (masih aktif), siapkan start_date di background setelah masa aktif habis
        const ug = selectedUserGym.value;
        if (ug && ug.membership_end_at) {
            membershipStartInput.value = toISODate(new Date(ug.membership_end_at));
        }
    }
});

const editableMembershipStartDate = computed(() => {
    if (membershipStartInput.value) return new Date(membershipStartInput.value);
    return new Date();
});

const editableMembershipEndDate = computed(() => {
    if (!editableMembershipStartDate.value || !selectedItem.value) return null;
    const days = selectedItem.value.duration_in_days || 0;
    const start = new Date(editableMembershipStartDate.value);
    const end = new Date(start.getTime() + days * 24 * 60 * 60 * 1000);
    return end;
});

const handleManualPayment = (id: number | string, action: 'approve' | 'reject') => {
    const label = action === 'approve' ? 'MENYETUJUI' : 'MENOLAK';

    if (confirm(`Apakah Anda yakin ingin ${label} pembayaran manual ini?`)) {
        router.post(`/management/user/transactions/${id}/manual-action`, {
            action: action
        }, {
            onSuccess: () => {
                notyf.success(`Pembayaran manual berhasil ${action === 'approve' ? 'disetujui' : 'ditolak'}!`);
            }
        });
    }
};

const handleGeneratePayment = async () => {
    if (!paymentForm.value.selected_item_id) {
        alert("Silakan pilih paket terlebih dahulu.");
        return;
    }

    isGenerating.value = true;

    try {
        const payload = {
            user_id: props.user.id,
            gym_id: paymentForm.value.gym_id,
            transaction_type: paymentForm.value.transaction_type,
            type_id: paymentForm.value.selected_item_id,
            payment_method: paymentForm.value.payment_type,
            payment_type: paymentForm.value.payment_mode,
            dp_percent: paymentForm.value.payment_mode === 'dp_payment' ? paymentForm.value.dp_percent : null,
            promo_code: paymentForm.value.promo_code || null,
        };

        // Otomatis menempelkan parameter start_at dari state watcher di atas
        if (paymentForm.value.transaction_type === 'membership') {
            if (membershipStartInput.value) {
                payload.start_at = membershipStartInput.value;
            }
        }

        const response = await axios.post('/management/user/generate-payment', payload);

        // Semua metode (manual, VA, QRIS) kini menggunakan alur yang sama:
        // transaksi dibuat dengan status pending dan perlu validasi admin.
        const method = paymentForm.value.payment_type;
        const methodLabel = method === 'va' ? 'Virtual Account' : method === 'qris' ? 'QRIS' : 'Manual';
        notyf.success(`Invoice ${methodLabel} berhasil dibuat! Menunggu validasi admin.`);

        router.reload({
            only: ['pendingTransactions'],
            onSuccess: () => {
                paymentForm.value.selected_item_id = '';
            }
        });

    } catch (error: any) {
        if (error.response?.status === 422) {
            const messages = Object.values(error.response.data.errors).flat().join('\n');
            alert("Validasi Gagal:\n" + messages);
        } else {
            alert("Gagal membuat pembayaran. Silakan coba lagi.");
        }
    } finally {
        isGenerating.value = false;
    }
};

const calculation = computed(() => {
    const basePrice = selectedItem.value?.price || 0;

    let totalDiscount = 0;
    let totalBonusDays = 0;
    let totalBonusSessions = 0;

    activePromos.value.forEach(promo => {
        if (promo.type === 'discount_percent') {
            totalDiscount += (basePrice * (promo.value / 100));
        } else if (promo.type === 'discount_amount') {
            totalDiscount += parseFloat(promo.value);
        } else if (promo.type === 'bonus_days') {
            totalBonusDays += parseInt(promo.value);
        } else if (promo.type === 'bonus_sessions') {
            totalBonusSessions += parseInt(promo.value);
        }
    });

    const subtotal = Math.max(0, basePrice - totalDiscount);
    const ppn = 0;

    let fee = 0;
    // Fee VA dan QRIS dihapus karena tidak lagi menggunakan payment gateway Midtrans

    const grandTotal = subtotal + fee;

    let payableNow = grandTotal;
    let remaining = 0;

    if (
        paymentForm.value.payment_mode === 'dp_payment' &&
        paymentForm.value.transaction_type === 'pt'
    ) {
        const percent = Math.max(20, paymentForm.value.dp_percent || 20);
        payableNow = grandTotal * (percent / 100);
        remaining = grandTotal - payableNow;
    }

    return {
        basePrice,
        totalDiscount,
        totalBonusDays,
        totalBonusSessions,
        subtotal,
        ppn,
        fee,
        grandTotal,
        payableNow,
        remaining
    };
});

const activePromos = computed(() => {
    if (!selectedItem.value) return [];
    const globals = paymentForm.value.transaction_type === 'membership'
        ? (selectedItem.value.membership_promos || [])
        : (selectedItem.value.pt_package_promos || []);

    const allPromos = [...globals];
    if (appliedManualPromo.value) {
        allPromos.push(appliedManualPromo.value);
    }
    return allPromos;
});

const verifyPromo = async () => {
    if (!paymentForm.value.promo_code) return;
    try {
        const res = await axios.post('/management/user/check-promo', {
            code: paymentForm.value.promo_code,
            id: paymentForm.value.selected_item_id,
            type: paymentForm.value.transaction_type
        });

        if (res.data.unique_code === null) {
            alert("Promo ini sudah aktif secara otomatis.");
            return;
        }

        appliedManualPromo.value = res.data;
        alert("Kode promo berhasil dipasang!");
    } catch (e) {
        alert("Kode promo tidak valid untuk paket ini.");
    }
};

const freezeForm = ref({
    gym_id: '' as string | number,
    freeze_count: 30,
});
const isFreezingLoading = ref(false);

const handleFreeze = async () => {
    if (!freezeForm.value.gym_id) {
        notyf.error('Silakan pilih gym terlebih dahulu.');
        return;
    }
    if (!freezeForm.value.freeze_count || freezeForm.value.freeze_count % 30 !== 0) {
        notyf.error('Jumlah hari freeze harus kelipatan 30.');
        return;
    }

    isFreezingLoading.value = true;
    try {
        await axios.post('/management/user/freeze', {
            user_id: props.user.id,
            gym_id: freezeForm.value.gym_id,
            freeze_count: freezeForm.value.freeze_count,
        });
        notyf.success('Transaction freezing berhasil dibuat.');
        router.reload({ only: ['pendingFreezings', 'userGyms'] });
    } catch (error: any) {
        const msg = error.response?.data?.message || 'Gagal memproses freeze.';
        notyf.error(msg);
    } finally {
        isFreezingLoading.value = false;
    }
};

const handleUnfreeze = async (gymId: string | number) => {
    if (!confirm('Apakah Anda yakin ingin meng-unfreeze user di gym ini?')) return;

    isFreezingLoading.value = true;
    try {
        await axios.post('/management/user/unfreeze', {
            user_id: props.user.id,
            gym_id: gymId,
        });
        notyf.success('User berhasil di-unfreeze.');
        router.reload({ only: ['pendingFreezings', 'userGyms'] });
    } catch (error: any) {
        const msg = error.response?.data?.message || 'Gagal memproses unfreeze.';
        notyf.error(msg);
    } finally {
        isFreezingLoading.value = false;
    }
};

const handleUpdateTransactionFreezing = async (id: number | string, status: 'success' | 'failed') => {
    const label = status === 'success' ? 'MENYETUJUI' : 'MENOLAK';
    if (!confirm(`Apakah Anda yakin ingin ${label} transaksi freeze ini?`)) return;

    isFreezingLoading.value = true;
    try {
        await axios.post('/management/user/update-transaction-freezing', {
            transaction_freezing_id: id,
            status: status,
        });
        notyf.success(`Transaksi freeze berhasil ${status === 'success' ? 'disetujui' : 'ditolak'}.`);
        router.reload({ only: ['pendingFreezings', 'userGyms'] });
    } catch (error: any) {
        const msg = error.response?.data?.message || 'Gagal memproses transaksi freeze.';
        notyf.error(msg);
    } finally {
        isFreezingLoading.value = false;
    }
};

const breadcrumbItems = [
    { title: 'Management User', href: '/management/user' },
    { title: 'Edit User', href: '#' },
];

const form = useForm({
    email: props.user.email || '',
    name: props.user.name || '',
    password: '',

    nik: props.user.user_detail?.nik || '',
    birth_place: props.user.user_detail?.birth_place || '',
    birth_date: props.user.user_detail?.birth_date || '',
    gender: props.user.user_detail?.gender || '',
    address: props.user.user_detail?.address || '',
    phone_number: props.user.user_detail?.phone_number || '',
    photo: null as File | null,

    emergency_name: props.user.user_detail?.emergency_name || '',
    emergency_phone: props.user.user_detail?.emergency_phone || '',
    emergency_relation: props.user.user_detail?.emergency_relation || '',
    notes: props.user.user_detail?.notes || '',
});

const existingPhoto = ref(props.user.user_detail?.photo_url || null);
const photoPreview = ref<string | null>(null);

const onPhotoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (!target.files?.length) return;
    const file = target.files[0];
    if (file.size > 30 * 1024 * 1024) {
        alert('Ukuran foto maksimal 30MB. Silakan kompres atau pilih foto lain.');
        target.value = '';
        return;
    }
    form.photo = file;
    photoPreview.value = URL.createObjectURL(file);
};

const removePhoto = () => {
    form.photo = null;
    photoPreview.value = null;
    existingPhoto.value = null;
};

// Webcam capture
const showWebcam = ref(false);
const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
let mediaStream: MediaStream | null = null;

const openWebcam = async () => {
    showWebcam.value = true;
    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
        await new Promise(resolve => setTimeout(resolve, 100));
        if (videoRef.value) {
            videoRef.value.srcObject = mediaStream;
        }
    } catch {
        alert('Tidak dapat mengakses kamera. Pastikan browser memiliki izin kamera.');
        showWebcam.value = false;
    }
};

const closeWebcam = () => {
    if (mediaStream) {
        mediaStream.getTracks().forEach(t => t.stop());
        mediaStream = null;
    }
    showWebcam.value = false;
};

const capturePhoto = () => {
    if (!videoRef.value || !canvasRef.value) return;
    const video = videoRef.value;
    const canvas = canvasRef.value;
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d')!.drawImage(video, 0, 0);
    canvas.toBlob(blob => {
        if (!blob) return;
        const file = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
        existingPhoto.value = null;
        closeWebcam();
    }, 'image/jpeg', 0.9);
};

onUnmounted(() => {
    if (mediaStream) mediaStream.getTracks().forEach(t => t.stop());
});

const submit = () => {
    form.post(`/management/user/${props.user.id}`, {
        onFinish: () => form.reset('password'),
        forceFormData: true,
        headers: { 'X-HTTP-Method-Override': 'PUT' },
    });
};

const qrRef = ref<InstanceType<typeof QrcodeVue> | null>(null);
const downloadSVG = () => {
    const svgElement = qrRef.value?.$el;
    const serializer = new XMLSerializer();
    let source = serializer.serializeToString(svgElement);

    if (!source.match(/^<svg[^>]+xmlns="http\:\/\/www\.w3\.org\/2000\/svg"/)) {
        source = source.replace(/^<svg/, '<svg xmlns="http://www.w3.org/2000/svg"');
    }

    const svgBlob = new Blob([source], { type: "image/svg+xml;charset=utf-8" });
    const url = URL.createObjectURL(svgBlob);

    const downloadLink = document.createElement("a");
    downloadLink.href = url;
    downloadLink.download = `QR_Code_${props.user.name.replace(/\s+/g, '_')}.svg`;
    document.body.appendChild(downloadLink);
    downloadLink.click();

    document.body.removeChild(downloadLink);
    URL.revokeObjectURL(url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Edit User - ${props.user.name}`" />

        <div class="min-h-screen bg-muted/40 py-6 md:py-10">
            <div class="max-w-7xl mx-auto px-4 md:px-6 space-y-8">
                <form @submit.prevent="submit" class="space-y-8">

                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-center gap-4">
                            <Heading :title="`Edit User`"
                                description="Perbarui informasi profil dan detail pengguna." />
                        </div>
                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <button type="submit" :disabled="form.processing"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 disabled:opacity-50 cursor-pointer">
                                <component :is="form.processing ? Loader2 : Save" :size="18"
                                    :class="{ 'animate-spin': form.processing }" />
                                <span>Simpan Perubahan</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">

                        <div class="md:col-span-1">
                            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-6">
                                <h3 class="font-semibold flex items-center gap-2 text-foreground mb-4">
                                    <Lock :size="18" class="text-primary" />
                                    Credential
                                </h3>
                                <hr class="border-muted mb-4" />

                                <div class="space-y-6">
                                    <div
                                        class="relative group w-full max-w-[240px] mx-auto border p-4 rounded-xl overflow-hidden bg-white shadow-inner">
                                        <div class="qr-wrapper flex justify-center items-center">
                                            <qrcode-vue ref="qrRef" :value="props.user.unique_id" :size="500" level="H"
                                                render-as="svg" class="w-full h-auto" />
                                        </div>

                                        <div
                                            class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                            <button type="button" @click.stop.prevent="downloadSVG"
                                                class="cursor-pointer bg-white text-black px-4 py-2 rounded-lg shadow-xl font-bold flex items-center gap-2 hover:bg-gray-100 transition-colors text-xs">
                                                <Download :size="14" />
                                                <span>Download QR</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Foto User</label>
                                        <div class="flex flex-col items-center gap-3">
                                            <div v-if="photoPreview || existingPhoto"
                                                class="relative w-28 h-28 rounded-xl overflow-hidden border shadow-sm">
                                                <img :src="photoPreview || existingPhoto || ''" alt="Photo"
                                                    class="w-full h-full object-cover" />
                                                <button type="button" @click="removePhoto"
                                                    class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 cursor-pointer">
                                                    ✕
                                                </button>
                                            </div>
                                            <label
                                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border-2 border-dashed border-primary/50 text-primary hover:bg-primary/5 transition-all text-xs font-bold cursor-pointer">
                                                <ImagePlus :size="16" />
                                                <span>{{ (photoPreview || existingPhoto) ? 'Ganti Foto' : 'Upload Foto' }}</span>
                                                <input type="file" accept="image/*" class="hidden"
                                                    @change="onPhotoChange" />
                                            </label>
                                            <button type="button" @click="openWebcam"
                                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border-2 border-dashed border-primary/50 text-primary hover:bg-primary/5 transition-all text-xs font-bold cursor-pointer">
                                                <Camera :size="16" />
                                                <span>Ambil Foto</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="space-y-2">
                                            <label class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Nama Lengkap</label>
                                            <Input v-model="form.name" :error="form.errors.name" class="rounded-xl" />
                                        </div>
                                        <div class="space-y-2">
                                            <label
                                                class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Email</label>
                                            <Input v-model="form.email" type="email" :error="form.errors.email"
                                                class="rounded-xl bg-muted/40" disabled />
                                        </div>
                                        <div class="space-y-2">
                                            <label
                                                class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Password
                                                Baru (Opsional)</label>
                                            <Input v-model="form.password" type="password" placeholder="Biarkan kosong"
                                                :error="form.errors.password" class="rounded-xl" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2 space-y-6">
                            <div class="rounded-2xl border bg-background p-6 shadow-sm">
                                <div
                                    class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
                                    <h3 class="font-semibold flex items-center gap-2 text-foreground">
                                        <UserIcon :size="18" class="text-primary" />
                                        User Detail
                                    </h3>
                                </div>

                                <hr class="border-muted mb-6" />

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="space-y-2 sm:col-span-2">
                                        <label class="text-xs font-medium uppercase tracking-wider text-muted-foreground">NIK</label>
                                        <Input v-model="form.nik" :error="form.errors.nik" class="rounded-xl" />
                                    </div>
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Tempat
                                            Lahir</label>
                                        <Input v-model="form.birth_place" class="rounded-xl" />
                                    </div>
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground flex items-center gap-1">
                                            <Calendar :size="12" /> Tanggal Lahir
                                        </label>
                                        <Input v-model="form.birth_date" type="date" class="rounded-xl" />
                                    </div>
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Jenis
                                            Kelamin</label>
                                        <select v-model="form.gender"
                                            class="flex h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm">
                                            <option value="male">Laki-laki</option>
                                            <option value="female">Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground flex items-center gap-1">
                                            <Phone :size="12" /> Nomor Telepon
                                        </label>
                                        <Input v-model="form.phone_number" class="rounded-xl" />
                                    </div>
                                    <div class="space-y-2 sm:col-span-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground flex items-center gap-1">
                                            <MapPin :size="12" /> Alamat Lengkap
                                        </label>
                                        <Textarea v-model="form.address" rows="3" class="rounded-xl" />
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-6">
                                <h3 class="font-semibold flex items-center gap-2 text-foreground">
                                    <Heart :size="18" class="text-primary" />
                                    Kontak Darurat
                                </h3>
                                <hr class="border-muted" />

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="space-y-2 sm:col-span-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Nama Kontak Darurat</label>
                                        <Input v-model="form.emergency_name" placeholder="Nama kontak darurat"
                                            class="rounded-xl" />
                                    </div>
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground flex items-center gap-1">
                                            <Phone :size="12" /> No HP Darurat
                                        </label>
                                        <Input v-model="form.emergency_phone" placeholder="08xxxxxxxxxx"
                                            class="rounded-xl" />
                                    </div>
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Hubungan</label>
                                        <Input v-model="form.emergency_relation" placeholder="Contoh: Orang tua, Saudara"
                                            class="rounded-xl" />
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-6">
                                <h3 class="font-semibold flex items-center gap-2 text-foreground">
                                    <MapPin :size="18" class="text-primary" />
                                    Catatan
                                </h3>
                                <hr class="border-muted" />

                                <div class="space-y-2">
                                    <label
                                        class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Catatan / Deskripsi</label>
                                    <Textarea v-model="form.notes" rows="4" placeholder="Catatan tambahan mengenai user (opsional)"
                                        class="rounded-xl" />
                                </div>
                            </div>
                        </div>

                    </div>
                </form>

                <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-6">
                    <div class="flex items-center gap-2">
                        <CreditCard class="text-primary" :size="20" />
                        <h3 class="font-bold text-lg">Pembayaran</h3>
                    </div>
                    <hr class="border-muted" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase text-muted-foreground">Pilih Gym</label>
                                <select v-model="paymentForm.gym_id" class="w-full h-10 rounded-xl border px-3 text-sm">
                                    <option value="" disabled>Pilih lokasi latihan</option>
                                    <option v-for="gym in gyms" :key="gym.id" :value="gym.id">{{ gym.name }}</option>
                                </select>
                            </div>

                            <div v-if="paymentForm.gym_id" class="space-y-2">
                                <label class="text-xs font-bold uppercase text-muted-foreground">Jenis Transaksi</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button" @click="paymentForm.transaction_type = 'membership'"
                                        :class="paymentForm.transaction_type === 'membership' ? 'bg-primary text-white' : 'bg-muted'"
                                        class="p-3 rounded-xl text-sm font-medium transition-all">Membership</button>
                                    <button type="button" @click="paymentForm.transaction_type = 'pt'"
                                        :class="paymentForm.transaction_type === 'pt' ? 'bg-primary text-white' : 'bg-muted'"
                                        class="p-3 rounded-xl text-sm font-medium transition-all">Personal
                                        Trainer</button>
                                </div>
                            </div>

                            <div v-if="paymentForm.transaction_type"
                                class="space-y-3 max-h-[300px] overflow-y-auto pr-2">
                                <div v-for="item in (paymentForm.transaction_type === 'membership' ? gymData.memberships : gymData.pt_packages)"
                                    :key="item.id" @click="paymentForm.selected_item_id = item.id"
                                    :class="paymentForm.selected_item_id === item.id ? 'border-primary ring-1 ring-primary' : 'border-muted'"
                                    class="p-4 border rounded-xl cursor-pointer hover:bg-muted/50 transition-all relative">

                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-bold text-sm">{{ item.name }}</p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ paymentForm.transaction_type === 'membership' ? item.duration_in_days
                                                    + ' Hari' : item.duration_in_sessions + ' Sesi' }}
                                            </p>
                                        </div>
                                        <p class="font-bold text-primary">{{ formatRupiah(item.price) }}</p>
                                    </div>

                                    <div v-if="item.membership_promos?.[0] || item.pt_package_promos?.[0]"
                                        class="mt-2 inline-flex items-center gap-1 bg-green-100 text-green-700 px-2 py-0.5 rounded-md text-[10px] font-bold">
                                        <Ticket :size="10" /> PROMO GLOBAL AKTIF
                                    </div>
                                </div>
                            </div>

                            <div v-if="paymentForm.selected_item_id" class="space-y-2">
                                <template v-if="showMembershipDateInput">
                                    <label class="text-xs font-medium uppercase text-muted-foreground">Tanggal Mulai / Selesai</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <input type="date" v-model="membershipStartInput" :min="toISODate(new Date())"
                                            class="w-full h-10 rounded-xl border border-input bg-background px-3 py-2 text-sm" />
                                        <input type="date" :value="toISODate(editableMembershipEndDate)" disabled
                                            class="w-full h-10 rounded-xl border border-input bg-background px-3 py-2 text-sm" />
                                    </div>
                                </template>

                                <label class="text-xs font-bold uppercase text-muted-foreground mt-4 block">
                                    Metode Pembayaran
                                </label>

                                <div class="grid grid-cols-3 gap-3">
                                    <button type="button" @click="paymentForm.payment_type = 'manual'" :class="paymentForm.payment_type === 'manual'
                                        ? 'bg-primary text-white ring-2 ring-primary'
                                        : 'bg-muted hover:bg-muted/70'"
                                        class="p-3 rounded-xl text-sm font-medium transition-all">
                                        <Store class="mx-auto mb-1" :size="16" />
                                        Manual
                                        <div class="text-[10px] opacity-70">Fee 0</div>
                                    </button>

                                    <button type="button" @click="paymentForm.payment_type = 'va'" :class="paymentForm.payment_type === 'va'
                                        ? 'bg-primary text-white ring-2 ring-primary'
                                        : 'bg-muted hover:bg-muted/70'"
                                        class="p-3 rounded-xl text-sm font-medium transition-all">
                                        <CreditCard class="mx-auto mb-1" :size="16" />
                                        Virtual Account
                                        <div class="text-[10px] opacity-70">Fee 0</div>
                                    </button>

                                    <button type="button" @click="paymentForm.payment_type = 'qris'" :class="paymentForm.payment_type === 'qris'
                                        ? 'bg-primary text-white ring-2 ring-primary'
                                        : 'bg-muted hover:bg-muted/70'"
                                        class="p-3 rounded-xl text-sm font-medium transition-all">
                                        <Ticket class="mx-auto mb-1" :size="16" />
                                        QRIS
                                        <div class="text-[10px] opacity-70">Fee 0</div>
                                    </button>
                                </div>
                            </div>

                            <div v-if="paymentForm.selected_item_id
                                && paymentForm.transaction_type === 'pt'" class="space-y-2">
                                <label class="text-xs font-bold uppercase text-muted-foreground">
                                    Jenis Pembayaran
                                </label>

                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button" @click="paymentForm.payment_mode = 'full_payment'" :class="paymentForm.payment_mode === 'full_payment'
                                        ? 'bg-primary text-white'
                                        : 'bg-muted hover:bg-muted/70'"
                                        class="p-3 rounded-xl text-sm font-medium transition-all">
                                        Full Payment
                                    </button>

                                    <button v-if="paymentForm.transaction_type === 'pt'" type="button"
                                        @click="paymentForm.payment_mode = 'dp_payment'" :class="paymentForm.payment_mode === 'dp_payment'
                                            ? 'bg-primary text-white'
                                            : 'bg-muted hover:bg-muted/70'"
                                        class="p-3 rounded-xl text-sm font-medium transition-all">
                                        DP Payment
                                    </button>
                                </div>

                                <div v-if="paymentForm.payment_mode === 'dp_payment'
                                    && paymentForm.transaction_type === 'pt'" class="mt-3">
                                    <label class="text-xs font-medium text-muted-foreground">
                                        Persentase DP (Min 20%)
                                    </label>

                                    <Input type="number" min="20" v-model.number="paymentForm.dp_percent"
                                        class="rounded-xl mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-muted/30 rounded-2xl p-6 space-y-4 border border-dashed flex flex-col h-full">
                            <h4
                                class="font-bold text-sm uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                                Rincian Pembayaran
                            </h4>

                            <div class="space-y-3 text-sm flex-1">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Harga Dasar</span>
                                    <span class="font-medium">{{ formatRupiah(calculation.basePrice) }}</span>
                                </div>

                                <div v-if="activePromos.length > 0"
                                    class="space-y-1.5 border-l-2 border-green-500 pl-3">
                                    <div v-for="(promo, index) in activePromos" :key="index" class="text-xs">
                                        <div class="flex justify-between text-green-700 font-medium">
                                            <span>{{ promo.unique_code === null ? '✨ Promo Global' : '🎫 Kode: ' +
                                                promo.unique_code }}</span>
                                            <span v-if="promo.type.includes('discount')">
                                                - {{ formatRupiah(promo.type === 'discount_percent' ?
                                                    calculation.basePrice *
                                                    (promo.value / 100) : promo.value) }}
                                            </span>
                                            <span v-else class="text-blue-600 font-bold">
                                                +{{ promo.value }} {{ promo.type === 'bonus_days' ? 'Hari' : 'Sesi' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <hr class="border-muted border-dashed" />

                                <div class="flex justify-between text-orange-600 italic">
                                    <span>Biaya Layanan ({{ paymentForm.payment_type.toUpperCase() }})</span>
                                    <span>+ {{ Math.round(calculation.fee).toLocaleString() }}</span>
                                </div>

                                <div v-if="calculation.totalBonusDays > 0 || calculation.totalBonusSessions > 0"
                                    class="p-3 bg-blue-50 rounded-xl border border-blue-100 flex items-center gap-3">
                                    <div class="bg-blue-500 text-white p-1.5 rounded-lg">
                                        <Dumbbell :size="16" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-blue-700 uppercase leading-none">Total
                                            Keuntungan Extra</p>
                                        <p class="text-xs font-bold text-blue-800">
                                            {{ calculation.totalBonusDays > 0 ? `+${calculation.totalBonusDays} Hari` :
                                                '' }}
                                            {{ calculation.totalBonusSessions > 0 ? `+${calculation.totalBonusSessions}
                                            Sesi` : '' }}
                                        </p>
                                    </div>
                                </div>

                                <hr class="border-muted border-dashed" />

                                <div class="flex justify-between font-bold">
                                    <span>Total Tagihan</span>
                                    <span>{{ formatRupiah(calculation.grandTotal) }}</span>
                                </div>

                                <div v-if="paymentForm.payment_mode === 'dp_payment'
                                    && paymentForm.transaction_type === 'pt'" class="space-y-2 mt-3">
                                    <div class="flex justify-between text-blue-600 font-semibold">
                                        <span>Bayar Sekarang ({{ paymentForm.dp_percent }}%)</span>
                                        <span>{{ formatRupiah(calculation.payableNow) }}</span>
                                    </div>

                                    <div class="flex justify-between text-red-500 text-sm">
                                        <span>Sisa Tagihan</span>
                                        <span>{{ formatRupiah(calculation.remaining) }}</span>
                                    </div>
                                </div>

                                <div v-else
                                    class="pt-4 flex justify-between font-black text-xl border-t-2 border-muted">
                                    <span>TOTAL BAYAR</span>
                                    <span class="text-primary font-mono">
                                        {{ formatRupiah(calculation.payableNow) }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-2 pt-4">
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <Input v-model="paymentForm.promo_code" placeholder="Punya kode promo lain?"
                                            class="rounded-xl h-11 pr-10" />
                                        <button v-if="appliedManualPromo"
                                            @click="appliedManualPromo = null; paymentForm.promo_code = ''"
                                            class="absolute right-3 top-3 text-red-500 hover:text-red-700">
                                            <X :size="16" />
                                        </button>
                                    </div>
                                    <button type="button" @click="verifyPromo"
                                        class="bg-foreground text-background px-6 rounded-xl font-bold hover:opacity-90 transition-opacity">
                                        Klaim
                                    </button>
                                </div>
                            </div>

                            <button type="button" @click="handleGeneratePayment"
                                :disabled="isGenerating || !paymentForm.selected_item_id"
                                class="w-full bg-primary text-white py-4 rounded-xl font-bold text-sm shadow-lg hover:shadow-primary/30 transition-all transform active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                <Loader2 v-if="isGenerating" :size="18" class="animate-spin" />
                                <span>{{ isGenerating ? 'MEMPROSES...' : 'GENERATE PEMBAYARAN' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-6 mt-8">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Clock class="text-orange-500" :size="20" />
                            <h3 class="font-bold text-lg">Transaksi Pending (Manual)</h3>
                        </div>
                        <span class="bg-orange-100 text-orange-700 text-xs px-3 py-1 rounded-full font-bold">
                            {{ pendingTransactions.length }} Perlu Validasi
                        </span>
                    </div>
                    <hr class="border-muted" />

                    <div v-if="pendingTransactions.length === 0" class="text-center py-8 text-muted-foreground">
                        <History :size="40" class="mx-auto mb-2 opacity-20" />
                        <p>Tidak ada transaksi manual yang menunggu persetujuan.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 gap-4">
                        <div v-for="trx in pendingTransactions" :key="trx.id"
                            class="flex flex-col md:flex-row items-start md:items-center justify-between p-4 border rounded-xl bg-orange-50/30 border-orange-100">

                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-orange-600">#{{ trx.unique_id
                                    }}</span>
                                    <span class="text-[10px] bg-white border px-2 py-0.5 rounded font-bold uppercase">{{
                                        trx.transaction_type }}</span>
                                </div>
                                <p class="font-bold text-sm">
                                    {{ trx.membership?.name || trx.full_pt?.name || trx.installment_pt?.name || 'Paket tidak diketahui' }}
                                </p>
                                <p class="text-xs text-muted-foreground italic">{{ trx.description }}</p>
                            </div>

                            <div
                                class="flex items-center gap-6 mt-4 md:mt-0 w-full md:w-auto justify-between md:justify-end">
                                <div class="text-right">
                                    <p class="text-[10px] uppercase font-bold text-muted-foreground leading-none">Total
                                        Tagihan</p>
                                    <p class="font-black text-primary">{{ formatRupiah(trx.total_price) }}</p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button @click="handleManualPayment(trx.id, 'approve')"
                                        class="p-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors shadow-sm cursor-pointer"
                                        title="Setujui Pembayaran">
                                        <CheckCircle :size="18" />
                                    </button>

                                    <button @click="handleManualPayment(trx.id, 'reject')"
                                        class="p-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors shadow-sm cursor-pointer"
                                        title="Tolak Pembayaran">
                                        <XCircle :size="18" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-6 mt-8">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <History class="text-blue-500" :size="20" />
                            <h3 class="font-bold text-lg">Tagihan Cicilan (Personal Trainer)</h3>
                        </div>
                        <span class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full font-bold">
                            {{ pendingInstallments.length }} Tagihan
                        </span>
                    </div>
                    <hr class="border-muted" />

                    <div v-if="pendingInstallments.length === 0" class="text-center py-8 text-muted-foreground">
                        <p>Tidak ada cicilan yang perlu dibayar.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 gap-6">
                        <div v-for="inst in pendingInstallments" :key="inst.id"
                            class="border rounded-2xl overflow-hidden bg-muted/10">

                            <div class="p-4 flex flex-col md:flex-row justify-between gap-4">
                                <div class="space-y-1">
                                    <p class="font-bold text-sm">{{ inst.description }}</p>
                                    <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                        <span>Tagihan: <b>{{ formatRupiah(parseFloat(inst.price)) }}</b></span>
                                        <span class="text-red-500">Jatuh Tempo: {{ inst.must_paid_before }}</span>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button v-for="m in availableMethods" :key="m"
                                        @click="installmentPaymentMethods[inst.id] = m"
                                        :class="installmentPaymentMethods[inst.id] === m ? 'bg-primary text-white border-primary' : 'bg-white border-muted'"
                                        class="px-3 py-1.5 border rounded-lg text-xs font-bold transition-all uppercase">
                                        {{ m }}
                                    </button>
                                </div>
                            </div>

                            <div
                                class="bg-muted/30 p-4 border-t flex flex-col md:flex-row items-center justify-between gap-4">
                                <div class="flex gap-4 text-xs">
                                    <div v-if="installmentPaymentMethods[inst.id]">
                                        <span class="text-muted-foreground">Biaya Layanan: </span>
                                        <span class="font-bold text-orange-600">+ {{
                                            formatRupiah(calculateInstallmentTotal(inst).fee) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Total Bayar: </span>
                                        <span class="font-black text-primary text-sm">{{
                                            formatRupiah(calculateInstallmentTotal(inst).total) }}</span>
                                    </div>
                                </div>

                                <button @click="handlePayInstallment(inst)"
                                    :disabled="isGenerating || !installmentPaymentMethods[inst.id]"
                                    class="w-full md:w-auto px-6 py-2 bg-foreground text-background rounded-xl font-bold text-xs hover:opacity-90 disabled:opacity-50 transition-all flex items-center justify-center gap-2 cursor-pointer">
                                    <Loader2 v-if="isGenerating" :size="14" class="animate-spin" />
                                    BAYAR SEKARANG
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-6 mt-8">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Snowflake class="text-cyan-500" :size="20" />
                            <h3 class="font-bold text-lg">Freeze Membership</h3>
                        </div>
                    </div>
                    <hr class="border-muted" />

                    <div v-if="userGyms.length > 0" class="space-y-3">
                        <h4 class="text-xs font-bold uppercase text-muted-foreground tracking-wider">Status Gym User</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div v-for="ug in userGyms" :key="ug.id"
                                :class="ug.freezed_at ? 'border-cyan-300 bg-cyan-50/30' : 'border-muted'"
                                class="p-4 border rounded-xl flex items-center justify-between">
                                <div class="space-y-1">
                                    <p class="font-bold text-sm">{{ ug.gym?.name || 'Gym #' + ug.gym_id }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        Membership s/d: {{ ug.membership_end_at ? new Date(ug.membership_end_at).toLocaleDateString('id-ID') : '-' }}
                                    </p>
                                    <div v-if="ug.freezed_at" class="flex items-center gap-1 text-xs text-cyan-600 font-medium">
                                        <Snowflake :size="12" />
                                        Freeze sejak {{ new Date(ug.freezed_at).toLocaleDateString('id-ID') }}
                                        s/d {{ ug.freezed_end_at ? new Date(ug.freezed_end_at).toLocaleDateString('id-ID') : '-' }}
                                    </div>
                                </div>
                                <button v-if="ug.freezed_at" @click="handleUnfreeze(ug.gym_id)"
                                    :disabled="isFreezingLoading"
                                    class="px-3 py-1.5 bg-orange-500 text-white rounded-lg text-xs font-bold hover:bg-orange-600 transition-colors disabled:opacity-50 cursor-pointer flex items-center gap-1">
                                    <ThermometerSun :size="14" />
                                    Unfreeze
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-xs font-bold uppercase text-muted-foreground tracking-wider">Buat Transaksi Freeze</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            <div class="space-y-2">
                                <label class="text-xs font-medium text-muted-foreground">Pilih Gym</label>
                                <select v-model="freezeForm.gym_id" class="w-full h-10 rounded-xl border px-3 text-sm">
                                    <option value="" disabled>Pilih gym</option>
                                    <option v-for="ug in userGyms.filter(u => !u.freezed_at)" :key="ug.gym_id" :value="ug.gym_id">
                                        {{ ug.gym?.name || 'Gym #' + ug.gym_id }}
                                    </option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-medium text-muted-foreground">Durasi Freeze (kelipatan 30 hari)</label>
                                <Input type="number" v-model.number="freezeForm.freeze_count" min="30" step="30" class="rounded-xl" />
                            </div>
                            <button type="button" @click="handleFreeze" :disabled="isFreezingLoading"
                                class="h-10 px-6 bg-cyan-600 text-white rounded-xl font-bold text-sm hover:bg-cyan-700 transition-colors disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2">
                                <Loader2 v-if="isFreezingLoading" :size="16" class="animate-spin" />
                                <Snowflake v-else :size="16" />
                                Generate Freeze
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold uppercase text-muted-foreground tracking-wider">Transaksi Freeze Pending</h4>
                            <span class="bg-cyan-100 text-cyan-700 text-xs px-3 py-1 rounded-full font-bold">
                                {{ pendingFreezings.length }} Menunggu
                            </span>
                        </div>

                        <div v-if="pendingFreezings.length === 0" class="text-center py-6 text-muted-foreground">
                            <Snowflake :size="32" class="mx-auto mb-2 opacity-20" />
                            <p class="text-sm">Tidak ada transaksi freeze yang menunggu persetujuan.</p>
                        </div>

                        <div v-else class="grid grid-cols-1 gap-4">
                            <div v-for="tf in pendingFreezings" :key="tf.id"
                                class="flex flex-col md:flex-row items-start md:items-center justify-between p-4 border rounded-xl bg-cyan-50/30 border-cyan-100">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-bold text-cyan-600">#{{ tf.id }}</span>
                                        <span class="text-[10px] bg-white border px-2 py-0.5 rounded font-bold uppercase">{{ tf.gym?.name }}</span>
                                    </div>
                                    <p class="font-bold text-sm">Freeze {{ tf.day_freeze }} hari</p>
                                    <p class="text-xs text-muted-foreground">Total: {{ formatRupiah(tf.total_price) }}</p>
                                </div>

                                <div class="flex items-center gap-2 mt-4 md:mt-0">
                                    <button @click="handleUpdateTransactionFreezing(tf.id, 'success')"
                                        :disabled="isFreezingLoading"
                                        class="p-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors shadow-sm cursor-pointer disabled:opacity-50"
                                        title="Setujui Freeze">
                                        <CheckCircle :size="18" />
                                    </button>
                                    <button @click="handleUpdateTransactionFreezing(tf.id, 'failed')"
                                        :disabled="isFreezingLoading"
                                        class="p-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors shadow-sm cursor-pointer disabled:opacity-50"
                                        title="Tolak Freeze">
                                        <XCircle :size="18" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showWebcam"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/70"
                @click.self="closeWebcam">
                <div class="bg-background rounded-2xl shadow-2xl p-6 w-full max-w-lg space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold flex items-center gap-2">
                            <Camera :size="18" class="text-primary" /> Ambil Foto via Kamera
                        </h3>
                        <button type="button" @click="closeWebcam"
                            class="rounded-full p-1.5 hover:bg-muted transition-colors">
                            <X :size="18" />
                        </button>
                    </div>
                    <div class="relative rounded-xl overflow-hidden bg-black aspect-video">
                        <video ref="videoRef" autoplay playsinline muted
                            class="w-full h-full object-cover" />
                    </div>
                    <canvas ref="canvasRef" class="hidden" />
                    <div class="flex gap-3">
                        <button type="button" @click="capturePhoto"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow hover:bg-primary/90 transition-colors cursor-pointer">
                            <Camera :size="16" /> Ambil Foto
                        </button>
                        <button type="button" @click="closeWebcam"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-medium hover:bg-muted transition-colors cursor-pointer">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>