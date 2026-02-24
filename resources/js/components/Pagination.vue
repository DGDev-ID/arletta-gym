<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links.length > 3" class="flex justify-end">
        <nav class="inline-flex rounded-md shadow-sm -space-x-px">
            <template v-for="(link, key) in links" :key="key">
                
                <!-- Disabled -->
                <span
                    v-if="link.url === null"
                    v-html="link.label"
                    class="px-3 py-2 text-sm text-gray-400 bg-gray-100 border border-gray-300 cursor-not-allowed"
                />

                <!-- Active & Normal -->
                <Link
                    v-else
                    :href="link.url"
                    v-html="link.label"
                    class="px-3 py-2 text-sm border border-gray-300"
                    :class="{
                        'z-10 bg-indigo-600 border-indigo-600 text-white': link.active,
                        'bg-white text-gray-700 hover:bg-gray-100': !link.active
                    }"
                />
            </template>
        </nav>
    </div>
</template>