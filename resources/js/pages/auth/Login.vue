<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
// note: using basic labels in template; no Label component required here
import { Spinner } from '@/components/ui/spinner';
// not using AuthBase layout in this variant
import { store } from '@/routes/login';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <!-- Full screen split layout similar to dashboard Login.vue -->
    <div class="min-h-screen flex">
        <!-- Left: Branding (hidden on small screens) -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-brand-600/30 via-transparent to-brand-500/20"></div>

            <div class="absolute -top-24 -left-24 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-32 -right-32 w-[500px] h-[500px] bg-brand-600/10 rounded-full blur-3xl"></div>
            <div class="absolute top-1/4 right-12 w-48 h-48 bg-brand-400/5 rounded-full blur-2xl"></div>

            <div class="relative z-10 flex flex-col justify-between px-12 xl:px-16 py-12 text-white w-full">
                <div class="flex items-center gap-3">
                    <img src="/logo-gym.svg" alt="Arletta Gym" class="w-10 h-10" />
                    <span class="text-xl font-bold tracking-tight">Arletta Gym</span>
                </div>

                <div class="space-y-8">
                    <div>
                        <h1 class="text-4xl xl:text-5xl font-bold leading-tight mb-4">
                            Kelola Gym Anda<br />
                        </h1>
                        <p class="text-lg text-gray-400 leading-relaxed max-w-md">
                            Platform manajemen gym terpadu untuk mengelola member, trainer, jadwal, dan pembayaran.
                        </p>
                    </div>

                    <div class="flex gap-8">
                        <div>
                            <div class="text-3xl font-bold text-brand-400">1,248</div>
                            <div class="text-sm text-gray-500 mt-1">Member Aktif</div>
                        </div>
                        <div class="w-px bg-gray-700"></div>
                        <div>
                            <div class="text-3xl font-bold text-brand-400">18</div>
                            <div class="text-sm text-gray-500 mt-1">Trainer</div>
                        </div>
                        <div class="w-px bg-gray-700"></div>
                        <div>
                            <div class="text-3xl font-bold text-brand-400">24/7</div>
                            <div class="text-sm text-gray-500 mt-1">Monitoring</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-5 h-5 rounded-full bg-brand-500/20">
                                <svg class="w-3 h-3 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-400">Manajemen Member</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-5 h-5 rounded-full bg-brand-500/20">
                                <svg class="w-3 h-3 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-400">Penjadwalan Kelas</span>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-5 h-5 rounded-full bg-brand-500/20">
                                <svg class="w-3 h-3 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-400">Manajemen Paket</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-5 h-5 rounded-full bg-brand-500/20">
                                <svg class="w-3 h-3 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-400">Pembayaran</span>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-5 h-5 rounded-full bg-brand-500/20">
                                <svg class="w-3 h-3 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-400">Laporan Keuangan</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-5 h-5 rounded-full bg-brand-500/20">
                                <svg class="w-3 h-3 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-400">Activity Log</span>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-gray-600">© 2026 Arletta Gym. All rights reserved.</p>
            </div>
        </div>

        <!-- Right: Login form -->
        <div class="flex-1 flex items-center justify-center px-6 py-12 bg-white dark:bg-gray-900">
            <div class="w-full max-w-[420px]">
                <div class="lg:hidden mb-10 text-center">
                    <img src="/logo-gym.svg" alt="Arletta Gym" class="w-14 h-14 mx-auto mb-3" />
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white/90">Arletta Gym</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sistem Manajemen Gym</p>
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white/90 mb-1.5">Masuk ke Dashboard</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Silakan masuk dengan akun Anda</p>
                </div>

                <Head title="Log in" />

                <div v-if="status" class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-700 border border-emerald-200">
                    {{ status }}
                </div>

                <Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }" class="space-y-5" >
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <Input id="email" type="email" name="email" required autofocus :tabindex="1" autocomplete="email" placeholder="nama@email.com"
                                class="h-12 w-full rounded-lg border border-gray-200 bg-gray-50 pl-12 pr-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 transition-colors focus:bg-white focus:border-brand-500 focus:outline-hidden focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-white/90 dark:placeholder:text-white/30" />
                        </div>
                        <InputError :message="errors.email" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <Input id="password" type="password" name="password" required :tabindex="2" autocomplete="current-password" placeholder="Masukkan password"
                                class="h-12 w-full rounded-lg border border-gray-200 bg-gray-50 pl-12 pr-12 py-3 text-sm text-gray-800 placeholder:text-gray-400 transition-colors focus:bg-white focus:border-brand-500 focus:outline-hidden focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-white/90 dark:placeholder:text-white/30" />
                        </div>
                        <InputError :message="errors.password" />
                    </div>

                    <!-- Submit Button: restored to previous style/behavior -->
                    <div>
                        <Button
                            type="submit"
                            class="mt-2 h-11 w-full rounded-xl bg-zinc-900 text-white font-semibold tracking-wide hover:bg-zinc-700 active:scale-[0.98] transition-all duration-200 shadow-md hover:shadow-lg disabled:opacity-60 disabled:cursor-not-allowed"
                            :tabindex="3"
                            :disabled="processing"
                            data-test="login-button"
                        >
                            <span class="flex items-center justify-center gap-2">
                                <Spinner v-if="processing" class="h-4 w-4" />
                                <span>{{ processing ? 'Signing in…' : 'Masuk' }}</span>
                            </span>
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </div>
</template>