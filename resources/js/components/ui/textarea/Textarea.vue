<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

const props = defineProps<{
    defaultValue?: string
    modelValue?: string
    class?: HTMLAttributes["class"]
}>()

const emits = defineEmits<{
    (e: "update:modelValue", payload: string): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
    passive: true,
    defaultValue: props.defaultValue,
})
</script>

<template>
    <textarea v-model="modelValue" data-slot="textarea" :class="cn(
        'w-full rounded-xl border bg-background px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary',
        props.class,
    )" />
</template>
