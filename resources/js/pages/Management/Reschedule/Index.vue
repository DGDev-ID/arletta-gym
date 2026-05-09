<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/layouts/AppLayout.vue'
import Pagination from '@/components/Pagination.vue'
import Heading from '@/components/Heading.vue'

const props = defineProps<{ userGyms: { data: any[]; links: any[] } }>()

const breadcrumbItems = [
    { title: 'Management Reschedule', href: '/management/user/reschedule' },
]

const showModal = ref(false)
const selected = ref<any|null>(null)
const newStart = ref<string>('')
const isSubmitting = ref(false)

const notyf = new Notyf({ duration: 3000, position: { x: 'right', y: 'bottom' } })

const openModal = (item: any) => {
    selected.value = item
    newStart.value = item.membership_start_at ? item.membership_start_at.split('T')[0] : ''
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    selected.value = null
}

const jumlahHari = computed(() => {
    if (!selected.value || !selected.value.membership_start_at || !selected.value.membership_end_at) return 0
    
    const start = new Date(selected.value.membership_start_at)
    const end = new Date(selected.value.membership_end_at)
    
    // Set jam ke 00:00:00 agar selisih harinya murni dan akurat
    start.setHours(0, 0, 0, 0)
    end.setHours(0, 0, 0, 0)
    
    return Math.max(0, Math.round((end.getTime() - start.getTime()) / (1000*60*60*24)))
})

const computedEnd = computed(() => {
    if (!newStart.value) return ''
    const start = new Date(newStart.value)
    start.setDate(start.getDate() + jumlahHari.value)
    
    // Gunakan padStart untuk merakit string YYYY-MM-DD secara lokal
    // (Menghindari bug timezone dari .toISOString())
    const year = start.getFullYear()
    const month = String(start.getMonth() + 1).padStart(2, '0')
    const day = String(start.getDate()).padStart(2, '0')
    
    return `${year}-${month}-${day}`
})

const submit = async () => {
    if (!selected.value) return
    isSubmitting.value = true
    try {
        await axios.post('/management/reschedule', {
            user_id: selected.value.user.id,
            gym_id: selected.value.gym.id,
            membership_start_at: newStart.value,
            membership_end_at: computedEnd.value, // <- KIRIMKAN END DATE KE BACKEND
        })
        notyf.success('Reschedule berhasil')
        window.location.reload()
    } catch (err: any) {
        notyf.error(err?.response?.data?.message || 'Gagal')
    } finally {
        isSubmitting.value = false
    }
}

const formatDate = (dateStr: string) => {
    if (!dateStr) return '-'
    const date = new Date(dateStr)
    
    // 'id-ID' akan memformat tanggal menjadi gaya Indonesia (DD/MM/YYYY)
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Reschedule Memberships" />

        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-6xl mx-auto px-6 space-y-8">

                <div class="flex items-center justify-between">
                    <Heading title="Reschedule Memberships" description="Kelola penjadwalan ulang membership yang akan dimulai" />
                </div>

                <div class="rounded-xl border bg-background overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-muted/50 text-muted-foreground font-medium border-b">
                                <tr>
                                    <th class="px-6 py-3">No</th>
                                    <th class="px-6 py-3">Email</th>
                                    <th class="px-6 py-3">Nama</th>
                                    <th class="px-6 py-3">Gym</th>
                                    <th class="px-6 py-3">Start Date</th>
                                    <th class="px-6 py-3">End Date</th>
                                    <th class="px-6 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="(ug, idx) in userGyms.data" :key="ug.id" class="hover:bg-muted/20 transition-colors">
                                    <td class="px-6 py-3">{{ idx+1 }}</td>
                                    <td class="px-6 py-3">{{ ug.user.email }}</td>
                                    <td class="px-6 py-3">{{ ug.user.name }}</td>
                                    <td class="px-6 py-3">{{ ug.gym?.name || '-' }}</td>
                                    <td class="px-6 py-3">{{ formatDate(ug.membership_start_at) }}</td>
                                    <td class="px-6 py-3">{{ formatDate(ug.membership_end_at) }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex gap-2">
                                            <button @click="openModal(ug)" class="inline-flex items-center justify-center rounded-lg bg-primary px-3 py-1 text-primary-foreground shadow-sm hover:bg-primary/90">Reschedule</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="(userGyms.data || []).length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-muted-foreground">No data</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 bg-background">
                        <Pagination :links="userGyms.links" />
                    </div>
                </div>

            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center">
            <div class="rounded-2xl border bg-background p-6 shadow-sm w-96">
                <h3 class="font-bold text-lg mb-4">Reschedule Membership</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium uppercase text-muted-foreground">Email</label>
                        <input type="text" :value="selected?.user?.email" disabled
                            class="w-full h-10 rounded-xl border border-input bg-muted/10 px-3 py-2 text-sm text-muted-foreground" />
                    </div>
                    <div>
                        <label class="text-xs font-medium uppercase text-muted-foreground">Gym</label>
                        <input type="text" :value="selected?.gym?.name" disabled
                            class="w-full h-10 rounded-xl border border-input bg-muted/10 px-3 py-2 text-sm text-muted-foreground" />
                    </div>
                    <div>
                        <label class="text-xs font-medium uppercase text-muted-foreground">Jumlah Hari Sisa</label>
                        <input type="text" :value="jumlahHari" disabled
                            class="w-full h-10 rounded-xl border border-input bg-muted/10 px-3 py-2 text-sm text-muted-foreground" />
                    </div>
                    <div>
                        <label class="text-xs font-medium uppercase text-muted-foreground">Start Date</label>
                        <input type="date" v-model="newStart"
                            :min="selected?.membership_start_at ? selected.membership_start_at.split('T')[0] : ''"
                            class="w-full h-10 rounded-xl border border-input bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs font-medium uppercase text-muted-foreground">End Date</label>
                        <input type="date" :value="computedEnd" disabled
                            class="w-full h-10 rounded-xl border border-input bg-muted/10 px-3 py-2 text-sm text-muted-foreground" />
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" @click="closeModal" class="px-3 py-1 rounded-lg border">Cancel</button>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="isSubmitting"
                        :class="['inline-flex items-center justify-center rounded-lg px-3 py-1 text-primary-foreground shadow-sm', isSubmitting ? 'bg-primary/60 opacity-60 cursor-not-allowed' : 'bg-primary hover:bg-primary/90']"
                    >
                        <span v-if="!isSubmitting">Save</span>
                        <span v-else>Saving...</span>
                    </button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
