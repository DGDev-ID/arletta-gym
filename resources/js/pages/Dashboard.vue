<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface Stats {
    total_users: number
    new_users: number
    active_users: number
    inactive_users: number
    total_transactions: number
    paid_transactions: number
    pending_transactions: number
    failed_transactions: number
    total_revenue: number
    bulanan_revenue: number
    personal_revenue: number
    tahunan_revenue: number
}

interface ChartData {
    months: string[]
    revenue: number[]
    new_members: number[]
}

interface RecentTransaction {
    unique_id: string
    member_name: string
    package: string
    amount: number
    date: string
    status: 'pending' | 'success' | 'failed'
}

const props = defineProps<{
    stats: Stats
    chart_data: ChartData
    recent_transactions: RecentTransaction[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// Format Rupiah
function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}

function formatRupiahShort(value: number): string {
    if (value >= 1_000_000_000) return `Rp ${(value / 1_000_000_000).toFixed(1)} M`
    if (value >= 1_000_000)     return `Rp ${Math.round(value / 1_000_000)} Jt`
    if (value >= 1_000)         return `Rp ${Math.round(value / 1_000)} Rb`
    return formatRupiah(value)
}

function getInitials(name: string): string {
    return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

const statusLabel: Record<string, string> = {
    success: 'Paid',
    pending: 'Pending',
    failed:  'Gagal',
}

const statusStyle: Record<string, string> = {
    success: 'inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400 ring-1 ring-inset ring-emerald-200 dark:ring-emerald-800',
    pending: 'inline-flex items-center gap-1.5 rounded-full bg-amber-50 dark:bg-amber-900/30 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:text-amber-400 ring-1 ring-inset ring-amber-200 dark:ring-amber-800',
    failed:  'inline-flex items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-900/30 px-2.5 py-1 text-xs font-semibold text-red-700 dark:text-red-400 ring-1 ring-inset ring-red-200 dark:ring-red-800',
}

const statusDot: Record<string, string> = {
    success: 'h-1.5 w-1.5 rounded-full bg-emerald-500',
    pending: 'h-1.5 w-1.5 rounded-full bg-amber-400',
    failed:  'h-1.5 w-1.5 rounded-full bg-red-500',
}

// Avatar colors cycling for member initials
const avatarColors = [
    'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
    'bg-violet-100 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300',
    'bg-teal-100 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300',
    'bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300',
]

const paidRate = computed(() => {
    if (!props.stats.total_transactions) return '0%'
    return (props.stats.paid_transactions / props.stats.total_transactions * 100).toFixed(1) + '%'
})

// Chart series from server data
const revenueSeries = computed(() => [
    { name: 'Pendapatan (Jt)', data: props.chart_data.revenue },
    { name: 'User Baru',       data: props.chart_data.new_members },
])

const revenueChartOptions = computed<any>(() => ({
    chart: { 
        type: 'area', 
        height: 260, 
        toolbar: { show: false }, 
        fontFamily: 'Outfit, sans-serif',
        background: 'transparent'
    },
    colors: ['#465FFF', '#10B981'],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    fill: { 
        type: 'gradient', 
        gradient: { 
            shadeIntensity: 1, 
            opacityFrom: 0.45, 
            opacityTo: 0.05, 
            stops: [0, 90, 100] 
        } 
    },
    xaxis: { 
        categories: props.chart_data.months,
        axisBorder: { show: false }, 
        axisTicks: { show: false },
        labels: { style: { fontSize: '12px', colors: '#6B7280', fontWeight: 500 } }
    },
    yaxis: { 
        labels: { 
            style: { fontSize: '12px', colors: '#6B7280', fontWeight: 500 },
            formatter: function(value: number) { return Math.round(value) }
        } 
    },
    grid: { borderColor: '#E5E7EB', strokeDashArray: 5, xaxis: { lines: { show: false } }, yaxis: { lines: { show: true } }, padding: { top: 0, right: 0, bottom: 0, left: 0 } },
    legend: { position: 'top', horizontalAlign: 'left', fontWeight: 600, fontSize: '13px', labels: { colors: '#374151' }, markers: { width: 10, height: 10, radius: 10 } },
    tooltip: { theme: 'light', style: { fontSize: '12px', fontFamily: 'Outfit, sans-serif' }, y: { formatter: function(value: number) { return value } } },
    markers: { size: 0, hover: { size: 5 } }
}))
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Dashboard" />

        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">

            <!-- Stat Cards -->
            <div class="grid gap-4 md:grid-cols-3">

                <!-- Total Users -->
                <Card class="border border-zinc-100 dark:border-zinc-800 shadow-sm bg-white dark:bg-zinc-900 rounded-2xl overflow-hidden">
                    <CardContent class="p-6">
                        <!-- Top row: icon + badge -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                                </svg>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/></svg>
                                +8.2%
                            </span>
                        </div>
                        <!-- Label + Value -->
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-0.5">Total Users</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-4">{{ stats.total_users.toLocaleString('id-ID') }}</p>
                        <!-- Footer stats -->
                        <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800 pt-3">
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Baru</p>
                                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">+{{ stats.new_users }}</p>
                            </div>
                            <div class="h-6 w-px bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Aktif</p>
                                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ stats.active_users }}</p>
                            </div>
                            <div class="h-6 w-px bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Nonaktif</p>
                                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ stats.inactive_users }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Transaksi Bulan Ini -->
                <Card class="border border-zinc-100 dark:border-zinc-800 shadow-sm bg-white dark:bg-zinc-900 rounded-2xl overflow-hidden">
                    <CardContent class="p-6">
                        <!-- Top row: icon + badge -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                                </svg>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                {{ paidRate }}
                            </span>
                        </div>
                        <!-- Label + Value -->
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-0.5">Transaksi Bulan Ini</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-4">{{ stats.total_transactions }}</p>
                        <!-- progress bar removed -->
                        <!-- Status breakdown -->
                        <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800 pt-3">
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Paid</p>
                                <p class="text-sm font-semibold text-emerald-600">{{ stats.paid_transactions }}</p>
                            </div>
                            <div class="h-6 w-px bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Pending</p>
                                <p class="text-sm font-semibold text-amber-500">{{ stats.pending_transactions }}</p>
                            </div>
                            <div class="h-6 w-px bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Gagal</p>
                                <p class="text-sm font-semibold text-red-500">{{ stats.failed_transactions }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pendapatan Bulan Ini -->
                <Card class="border border-zinc-100 dark:border-zinc-800 shadow-sm bg-white dark:bg-zinc-900 rounded-2xl overflow-hidden">
                    <CardContent class="p-6">
                        <!-- Top row: icon + badge -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 dark:bg-amber-900/40 text-amber-500 dark:text-amber-400">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/></svg>
                                +12.3%
                            </span>
                        </div>
                        <!-- Label + Value -->
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-0.5">Pendapatan Bulan Ini</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white mb-4">{{ formatRupiah(stats.total_revenue) }}</p>
                        <!-- Revenue breakdown -->
                        <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800 pt-3">
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Bulanan</p>
                                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ formatRupiahShort(stats.bulanan_revenue) }}</p>
                            </div>
                            <div class="h-6 w-px bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Personal</p>
                                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ formatRupiahShort(stats.personal_revenue) }}</p>
                            </div>
                            <div class="h-6 w-px bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Tahunan</p>
                                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ formatRupiahShort(stats.tahunan_revenue) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Transactions Table -->
                <Card class="border border-zinc-100 dark:border-zinc-800 shadow-sm bg-white dark:bg-zinc-900 rounded-2xl overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Statistik 12 bulan terakhir</h3>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-0.5">Pendapatan & Member Baru</p>
                            </div>
                        </div>
                        <div class="w-full">
                            <VueApexCharts type="area" height="260" :options="revenueChartOptions" :series="revenueSeries" />
                        </div>
                    </CardContent>
                </Card>

            <Card class="flex-1 border-0 shadow-sm bg-white dark:bg-zinc-900">
                <CardHeader class="px-6 py-5 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base font-semibold text-zinc-900 dark:text-white">Transaksi Terbaru</CardTitle>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Menampilkan {{ recent_transactions.length }} transaksi terakhir</p>
                        </div>
                        <Button variant="ghost" size="sm" class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                            Lihat semua
                            <svg class="ml-1 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/40">
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Member</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Paket</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Jumlah</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800">
                                <template v-if="recent_transactions.length > 0">
                                    <tr
                                        v-for="(tx, idx) in recent_transactions"
                                        :key="tx.unique_id"
                                        class="group hover:bg-zinc-50/80 dark:hover:bg-zinc-800/50 transition-colors duration-150"
                                    >
                                        <td class="px-6 py-4 font-mono text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            {{ tx.unique_id.substring(0, 8).toUpperCase() }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold"
                                                    :class="avatarColors[idx % avatarColors.length]"
                                                >{{ getInitials(tx.member_name) }}</div>
                                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ tx.member_name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">{{ tx.package }}</td>
                                        <td class="px-6 py-4 font-semibold text-zinc-800 dark:text-zinc-200">{{ formatRupiah(tx.amount) }}</td>
                                        <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">{{ tx.date }}</td>
                                        <td class="px-6 py-4">
                                            <span :class="statusStyle[tx.status]">
                                                <span :class="statusDot[tx.status]"></span>
                                                {{ statusLabel[tx.status] }}
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-zinc-400 dark:text-zinc-500">
                                        Belum ada transaksi
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Export Button (full width) -->
            <div class="mt-4">
                <Button
                    class="mt-2 h-11 w-full rounded-xl bg-zinc-900 text-white font-semibold tracking-wide hover:bg-zinc-700 active:scale-[0.98] transition-all duration-200 shadow-md hover:shadow-lg disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 3-3m-3 3-3-3M21 21H3" />
                    </svg>
                    Export Transaksi
                </Button>
            </div>

        </div>
    </AppLayout>
</template>