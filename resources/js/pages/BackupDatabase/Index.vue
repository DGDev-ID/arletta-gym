<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';

interface BackupRecord {
    id: number;
    name: string;
    url: string | null;
    created_at: string;
}

defineProps<{
    backups: {
        data: BackupRecord[];
        links: any[];
        total: number;
    };
}>();

const breadcrumbItems = [
    { title: 'Backup Database', href: '/backup-database' },
];

const showConfirmModal = ref(false);
const isDispatching = ref(false);

function openConfirm() {
    showConfirmModal.value = true;
}

function closeConfirm() {
    showConfirmModal.value = false;
}

function confirmBackup() {
    isDispatching.value = true;
    router.post('/backup-database/dispatch', {}, {
        onFinish: () => {
            isDispatching.value = false;
            showConfirmModal.value = false;
        },
    });
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Backup Database" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <Heading
                        title="Backup Database"
                        description="Kelola backup database dan lihat riwayat backup yang telah dilakukan."
                    />

                    <button
                        @click="openConfirm"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-primary-foreground font-semibold text-sm hover:opacity-90 transition-opacity shadow-sm"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Backup Database
                    </button>
                </div>

                <!-- Data Table -->
                <div class="rounded-xl border bg-background overflow-hidden shadow-sm">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/50 text-muted-foreground font-medium border-b">
                            <tr>
                                <th class="px-6 py-4">#</th>
                                <th class="px-6 py-4">Nama File</th>
                                <th class="px-6 py-4">URL</th>
                                <th class="px-6 py-4">Tanggal Backup</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="(backup, idx) in backups.data" :key="backup.id" class="hover:bg-muted/20 transition-colors">
                                <td class="px-6 py-4 text-muted-foreground">{{ idx + 1 }}</td>
                                <td class="px-6 py-4 font-medium font-mono text-xs">{{ backup.name }}</td>
                                <td class="px-6 py-4">
                                    <a
                                        v-if="backup.url"
                                        :href="backup.url"
                                        target="_blank"
                                        class="text-primary hover:underline inline-flex items-center gap-1 text-xs"
                                    >
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Download
                                    </a>
                                    <span v-else class="text-xs text-muted-foreground italic">-</span>
                                </td>
                                <td class="px-6 py-4 text-muted-foreground">{{ formatDate(backup.created_at) }}</td>
                            </tr>
                            <tr v-if="backups.data.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-muted-foreground italic">
                                    Belum ada data backup.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="p-4 border-t bg-background">
                        <Pagination :links="backups.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="showConfirmModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
                @click.self="closeConfirm"
            >
                <div class="bg-background rounded-2xl shadow-2xl p-6 w-full max-w-sm mx-4 border">
                    <div class="flex flex-col items-center gap-4 text-center">
                        <div class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-500">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-foreground">Konfirmasi Backup</h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Apakah Anda yakin ingin melakukan backup database sekarang? Proses ini akan berjalan di background.
                            </p>
                        </div>
                        <div class="flex gap-3 w-full">
                            <button
                                @click="closeConfirm"
                                class="flex-1 px-4 py-2.5 rounded-xl border text-sm font-medium hover:bg-muted transition-colors"
                                :disabled="isDispatching"
                            >
                                Batal
                            </button>
                            <button
                                @click="confirmBackup"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-primary text-primary-foreground text-sm font-semibold hover:opacity-90 transition-opacity disabled:opacity-50"
                                :disabled="isDispatching"
                            >
                                <span v-if="isDispatching" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 22 6.477 22 12h-4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Memproses...
                                </span>
                                <span v-else>Oke, Backup</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
