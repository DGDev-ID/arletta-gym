<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import {
    UserPlus, ScanLine, Lock, User, MapPin,
    Phone, Calendar, Loader2, Camera, Upload, ChevronDown
} from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import Input from "@/components/ui/input/Input.vue";
import Textarea from "@/components/ui/textarea/Textarea.vue";
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbItems = [
    { title: 'Management User', href: '/management/user' },
    { title: 'Tambah User', href: '/management/user/create' },
];

const form = useForm({
    email: '',
    name: '',
    password: '',

    nik: '',
    birth_place: '',
    birth_date: '',
    gender: '',
    address: '',
    phone_number: '',
});

const isScanning = ref(false);
const showOCRDropdown = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);
const cameraInputRef = ref<HTMLInputElement | null>(null);

const triggerFile = (type: 'camera' | 'file') => {
    showOCRDropdown.value = false;
    if (type === 'camera') cameraInputRef.value?.click();
    else fileInputRef.value?.click();
};

const handleFileSelected = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (!target.files?.length) return;

    const file = target.files[0];
    const formData = new FormData();
    formData.append('ktp_image', file);

    isScanning.value = true;

    try {
        const response = await axios.post('/management/user/ocr', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const data = response.data;

        if (data.nik) form.nik = data.nik;
        if (data.name) form.name = data.name;
        if (data.birth_place) form.birth_place = data.birth_place;
        if (data.birth_date) form.birth_date = data.birth_date;
        if (data.address) form.address = data.address;
        if (data.gender) form.gender = data.gender === 'LAKI-LAKI' ? 'male' : 'female';
        if (data.phone_number) form.phone_number = data.phone_number;

        alert("Scan Berhasil! Silakan periksa kembali data yang terisi.");
    } catch (error: any) {
        console.error(error);
        alert(error.response?.data?.message || "Gagal memproses OCR. Pastikan gambar KTP jelas.");
    } finally {
        isScanning.value = false;
        target.value = '';
    }
};

const submit = () => {
    form.post('/management/user', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Tambah User Baru" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <form @submit.prevent="submit" class="space-y-8">

                    <div class="flex flex-row justify-between items-start">
                        <Heading title="Tambah User Baru"
                            description="Daftarkan pengguna baru." />
                        <div class="flex items-center gap-3">
                            <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 disabled:opacity-50 cursor-pointer">
                                <UserPlus :size="18" />
                                <span>Simpan User</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-8">

                        <div class="md:col-span-1 space-y-6">
                            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">
                                <h3 class="font-semibold flex items-center gap-2 text-foreground">
                                    <Lock :size="18" class="text-primary" />
                                    Credential
                                </h3>
                                <hr class="border-muted" />
                                <div class="space-y-4">
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Nama
                                            Lengkap</label>
                                        <Input v-model="form.name" placeholder="John Doe" :error="form.errors.name"
                                            class="rounded-xl" />
                                    </div>
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Email</label>
                                        <Input v-model="form.email" type="email" placeholder="john@example.com"
                                            :error="form.errors.email" class="rounded-xl" />
                                    </div>
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Password</label>
                                        <Input v-model="form.password" type="password" placeholder="••••••••"
                                            :error="form.errors.password" class="rounded-xl" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2 space-y-6">
                            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-6">
                                <div class="flex items-center justify-between relative">
                                    <h3 class="font-semibold flex items-center gap-2 text-foreground">
                                        <User :size="18" class="text-primary" />
                                        User Detail
                                    </h3>

                                    <div class="relative">
                                        <button type="button" @click="showOCRDropdown = !showOCRDropdown"
                                            :disabled="isScanning"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border-2 border-dashed border-primary/50 text-primary hover:bg-primary/5 transition-all text-xs font-bold cursor-pointer disabled:opacity-50">
                                            <component :is="isScanning ? Loader2 : ScanLine"
                                                :class="{ 'animate-spin': isScanning }" :size="16" />
                                            <span>{{ isScanning ? 'Memproses...' : 'SCAN KTP' }}</span>
                                            <ChevronDown :size="14" class="transition-transform"
                                                :class="{ 'rotate-180': showOCRDropdown }" />
                                        </button>

                                        <div v-if="showOCRDropdown" v-click-outside="() => showOCRDropdown = false"
                                            class="absolute right-0 mt-2 w-48 bg-background border rounded-xl shadow-xl z-20 py-2 overflow-hidden animate-in fade-in zoom-in duration-200">
                                            <button type="button" @click="triggerFile('camera')"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-muted text-left transition-colors">
                                                <Camera :size="16" class="text-primary" />
                                                Ambil Foto
                                            </button>
                                            <button type="button" @click="triggerFile('file')"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-muted text-left transition-colors">
                                                <Upload :size="16" class="text-primary" />
                                                Unggah File
                                            </button>
                                        </div>
                                    </div>

                                    <input type="file" ref="fileInputRef" class="hidden" accept="image/*"
                                        @change="handleFileSelected" />
                                    <input type="file" ref="cameraInputRef" class="hidden" accept="image/*"
                                        capture="environment" @change="handleFileSelected" />
                                </div>
                                <hr class="border-muted" />

                                <div class="grid md:grid-cols-2 gap-6">
                                    <div class="space-y-2 md:col-span-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground">NIK</label>
                                        <Input v-model="form.nik" placeholder="16 digit NIK" :error="form.errors.nik"
                                            class="rounded-xl" />
                                    </div>

                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Tempat
                                            Lahir</label>
                                        <Input v-model="form.birth_place" placeholder="Contoh: Jakarta"
                                            class="rounded-xl" />
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
                                            class="flex h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring">
                                            <option value="">Pilih...</option>
                                            <option value="male">Laki-laki</option>
                                            <option value="female">Perempuan</option>
                                        </select>
                                    </div>

                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground flex items-center gap-1">
                                            <Phone :size="12" /> Nomor Telepon
                                        </label>
                                        <Input v-model="form.phone_number" placeholder="0812..." class="rounded-xl" />
                                    </div>

                                    <div class="space-y-2 md:col-span-2">
                                        <label
                                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground flex items-center gap-1">
                                            <MapPin :size="12" /> Alamat Lengkap
                                        </label>
                                        <Textarea v-model="form.address" placeholder="Jalan raya no..." rows="3"
                                            class="rounded-xl" />
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>