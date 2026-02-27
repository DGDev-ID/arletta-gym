<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'History Transaction', href: '/transaction/history' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="History Transaction" />

        <h1 class="sr-only">History Transaction</h1>
    </AppLayout>
</template>
