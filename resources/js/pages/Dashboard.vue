<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// Chart data (static for now) — matches dashboard vue
const revenueSeries = ref([
        { name: 'Pendapatan (Jt)', data: [65, 72, 68, 75, 82, 78, 85, 90, 88, 92, 84, 95] },
        { name: 'User Baru', data: [28, 35, 30, 32, 40, 38, 42, 48, 45, 50, 38, 52] },
])

// compute last 12 months labels (oldest -> newest)
function last12Months(locale = 'id') {
    const fmt = new Intl.DateTimeFormat(locale, { month: 'short' })
    const months: string[] = []
    const now = new Date()
    for (let i = 11; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
        // format like 'Feb 2026' and normalize (remove trailing dot in some locales)
        months.push(`${fmt.format(d).replace('.', '')} ${d.getFullYear()}`)
    }
    return months
}

const months = last12Months('id')

const revenueChartOptions = ref<any>({
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
        categories: months,
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
})
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
                        <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-4">1,248</p>
                        <!-- Footer stats -->
                        <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800 pt-3">
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Baru</p>
                                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">+98</p>
                            </div>
                            <div class="h-6 w-px bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Aktif</p>
                                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">892</p>
                            </div>
                            <div class="h-6 w-px bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Nonaktif</p>
                                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">356</p>
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
                                71.5%
                            </span>
                        </div>
                        <!-- Label + Value -->
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-0.5">Transaksi Bulan Ini</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-4">256</p>
                        <!-- progress bar removed -->
                        <!-- Status breakdown -->
                        <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800 pt-3">
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Paid</p>
                                <p class="text-sm font-semibold text-emerald-600">210</p>
                            </div>
                            <div class="h-6 w-px bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Pending</p>
                                <p class="text-sm font-semibold text-amber-500">32</p>
                            </div>
                            <div class="h-6 w-px bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Gagal</p>
                                <p class="text-sm font-semibold text-red-500">14</p>
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
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white mb-4">Rp 52.000.000</p>
                        <!-- Target progress -->
                        <!-- Revenue breakdown -->
                        <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800 pt-3">
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Bulanan</p>
                                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Rp 18 Jt</p>
                            </div>
                            <div class="h-6 w-px bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Personal</p>
                                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Rp 12 Jt</p>
                            </div>
                            <div class="h-6 w-px bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="text-center">
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Tahunan</p>
                                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Rp 22 Jt</p>
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
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Menampilkan 3 transaksi terakhir</p>
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
                                <tr class="group hover:bg-zinc-50/80 dark:hover:bg-zinc-800/50 transition-colors duration-150">
                                    <td class="px-6 py-4 font-mono text-xs font-medium text-zinc-500 dark:text-zinc-400">#TRX-001</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 text-xs font-bold">AS</div>
                                            <span class="font-medium text-zinc-800 dark:text-zinc-200">Andi Setiawan</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">Paket Bulanan</td>
                                    <td class="px-6 py-4 font-semibold text-zinc-800 dark:text-zinc-200">Rp 150.000</td>
                                    <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">20 Feb 2026</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400 ring-1 ring-inset ring-emerald-200 dark:ring-emerald-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Paid
                                        </span>
                                    </td>
                                </tr>
                                <tr class="group hover:bg-zinc-50/80 dark:hover:bg-zinc-800/50 transition-colors duration-150">
                                    <td class="px-6 py-4 font-mono text-xs font-medium text-zinc-500 dark:text-zinc-400">#TRX-002</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-violet-100 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300 text-xs font-bold">SN</div>
                                            <span class="font-medium text-zinc-800 dark:text-zinc-200">Siti Nurhaliza</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">Paket Personal</td>
                                    <td class="px-6 py-4 font-semibold text-zinc-800 dark:text-zinc-200">Rp 300.000</td>
                                    <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">22 Feb 2026</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 dark:bg-amber-900/30 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:text-amber-400 ring-1 ring-inset ring-amber-200 dark:ring-amber-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                            Pending
                                        </span>
                                    </td>
                                </tr>
                                <tr class="group hover:bg-zinc-50/80 dark:hover:bg-zinc-800/50 transition-colors duration-150">
                                    <td class="px-6 py-4 font-mono text-xs font-medium text-zinc-500 dark:text-zinc-400">#TRX-003</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-teal-100 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300 text-xs font-bold">BS</div>
                                            <span class="font-medium text-zinc-800 dark:text-zinc-200">Budi Santoso</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">Paket Tahunan</td>
                                    <td class="px-6 py-4 font-semibold text-zinc-800 dark:text-zinc-200">Rp 1.500.000</td>
                                    <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">23 Feb 2026</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400 ring-1 ring-inset ring-emerald-200 dark:ring-emerald-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Paid
                                        </span>
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