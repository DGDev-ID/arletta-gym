<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import Input from "@/components/ui/input/Input.vue";
import { debounce } from 'lodash'; // Pastikan lodash terinstall atau gunakan timeout manual
import axios from 'axios';

interface User {
    id: number;
    name: string;
    email: string;
    gyms?: any[];
}

const props = defineProps<{
    admins: User[];
}>();

const breadcrumbItems = [
    { title: 'Management Admin', href: '/management/admin' },
];

// State untuk pencarian user baru
const searchQuery = ref('');
const searchResults = ref<User[]>([]);
const isSearching = ref(false);

const form = useForm({
    user_id: null as number | null,
});

// Fungsi pencarian dengan debounce agar tidak memberatkan server
const searchUsers = debounce(async (query: string) => {
    if (query.length < 3) {
        searchResults.value = [];
        return;
    }
    
    isSearching.value = true;
    try {
        const response = await axios.get(`/management/admin/search?email=${query}`);
        searchResults.value = response.data;
    } catch (error) {
        console.error("Gagal mencari user", error);
    } finally {
        isSearching.value = false;
    }
}, 500);

watch(searchQuery, (newVal) => {
    searchUsers(newVal);
});

const promoteToAdmin = (userId: number) => {
    if (confirm('Jadikan user ini sebagai Admin?')) {
        form.user_id = userId;
        form.post('/management/admin', {
            onSuccess: () => {
                searchQuery.value = '';
                searchResults.value = [];
            }
        });
    }
};

const revokeAdmin = (id: number) => {
    if (confirm('Cabut akses admin untuk user ini?')) {
        router.delete(`/management/admin/${id}`);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Management Admin" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">
                
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <Heading title="Management Admin" description="Cari user untuk dijadikan admin dan kelola daftar admin aktif." />
                    
                    <div class="relative w-full md:w-96">
                        <label class="text-xs font-medium mb-1.5 block text-muted-foreground italic">Cari email user untuk ditambah jadi admin:</label>
                        <Input 
                            v-model="searchQuery" 
                            placeholder="Ketik minimal 3 karakter email..." 
                            class="rounded-xl pr-10"
                        />
                        <div v-if="isSearching" class="absolute right-3 bottom-2.5">
                            <div class="animate-spin h-4 w-4 border-2 border-primary border-t-transparent rounded-full"></div>
                        </div>

                        <div v-if="searchResults.length > 0" class="absolute z-10 w-full mt-2 bg-background border rounded-xl shadow-lg overflow-hidden">
                            <div v-for="user in searchResults" :key="user.id" 
                                class="p-3 hover:bg-muted/50 flex items-center justify-between border-b last:border-0 transition-colors">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium">{{ user.name }}</span>
                                    <span class="text-xs text-muted-foreground">{{ user.email }}</span>
                                </div>
                                <button @click="promoteToAdmin(user.id)" class="text-xs bg-primary text-primary-foreground px-3 py-1.5 rounded-lg hover:opacity-90">
                                    Pilih
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-background overflow-hidden shadow-sm">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/50 text-muted-foreground font-medium border-b">
                            <tr>
                                <th class="px-6 py-4">Nama</th>
                                <th class="px-6 py-4">Email</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Plot Gym</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="admin in admins" :key="admin.id" class="hover:bg-muted/20 transition-colors">
                                <td class="px-6 py-4 font-medium">{{ admin.name }}</td>
                                <td class="px-6 py-4 text-muted-foreground">{{ admin.email }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full bg-green-100 text-green-700 text-[10px] font-bold uppercase tracking-wider">
                                        Admin Active
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <ul>
                                        <li v-for="gym in admin.gyms" :key="gym.id" class="text-xs text-muted-foreground">
                                            {{ gym.name }}
                                        </li>
                                    </ul>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="revokeAdmin(admin.id)" class="text-destructive hover:underline font-medium">
                                        Copot Akses
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="admins.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-muted-foreground italic">
                                    Belum ada user dengan role admin.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AppLayout>
</template>