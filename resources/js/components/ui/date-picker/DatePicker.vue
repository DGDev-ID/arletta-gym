<script setup lang="ts">
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: string | Date | null;
        disabled?: boolean;
        placeholder?: string;
        format?: string;
        teleport?: boolean | string;
    }>(),
    {
        modelValue: null,
        disabled: false,
        placeholder: 'dd/mm/yyyy',
        format: 'dd/MM/yyyy',
        teleport: 'body',
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
    (e: 'change', value: string | null): void;
}>();

const dateValue = computed({
    get: () => props.modelValue,
    set: (val: string | null) => {
        emit('update:modelValue', val);
        emit('change', val);
    },
});
</script>

<template>
    <div class="custom-datepicker-wrapper w-full">
        <VueDatePicker
            v-model="dateValue"
            model-type="yyyy-MM-dd"
            :format="format"
            :disabled="disabled"
            :placeholder="placeholder"
            :auto-apply="true"
            :enable-time-picker="false"
            :teleport="teleport"
            locale="id"
            class="dp-custom-input"
        >
            <template #dp-input="{ value }">
                <div class="relative w-full">
                    <input
                        type="text"
                        readonly
                        :value="value"
                        :disabled="disabled"
                        :placeholder="placeholder"
                        class="w-full h-10 rounded-xl border border-input bg-background px-3 py-2 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer transition-all"
                    />
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" x2="16" y1="2" y2="6"/>
                            <line x1="8" x2="8" y1="2" y2="6"/>
                            <line x1="3" x2="21" y1="10" y2="10"/>
                        </svg>
                    </div>
                </div>
            </template>
        </VueDatePicker>
    </div>
</template>

<style>
/* Custom styling to match Tailwind UI theme */
.dp-custom-input {
    --dp-font-family: inherit;
    --dp-border-radius: 0.75rem;
    --dp-cell-border-radius: 0.5rem;
    --dp-primary-color: #3b82f6;
    --dp-primary-text-color: #ffffff;
}
.dp__main {
    font-family: inherit;
}
.dp__theme_light {
    --dp-background-color: #ffffff;
    --dp-text-color: #1e293b;
    --dp-hover-color: #f1f5f9;
    --dp-hover-text-color: #0f172a;
    --dp-hover-icon-color: #64748b;
    --dp-primary-color: #0f172a;
    --dp-primary-text-color: #ffffff;
    --dp-secondary-color: #94a3b8;
    --dp-border-color: #e2e8f0;
    --dp-menu-border-color: #e2e8f0;
    --dp-border-color-hover: #cbd5e1;
    --dp-disabled-color: #f8fafc;
    --dp-scroll-bar-color: #cbd5e1;
    --dp-scroll-bar-background: #f1f5f9;
    --dp-icon-color: #64748b;
    --dp-danger-color: #ef4444;
    --dp-marker-color: #ef4444;
    --dp-tooltip-color: #f8fafc;
}
.dp__menu {
    border-radius: 0.75rem !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
    border: 1px solid #e2e8f0 !important;
    padding: 6px !important;
}
</style>
