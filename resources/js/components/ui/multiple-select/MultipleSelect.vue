<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from "vue"
import { X } from "lucide-vue-next"
import Input from "@/components/ui/input/Input.vue"
import { Badge } from "@/components/ui/badge"

interface Option {
    label: string
    value: string | number
}

const props = defineProps<{
    modelValue: (string | number)[]
    options: Option[]
    placeholder?: string
}>()

const emit = defineEmits<{
    (e: "update:modelValue", value: (string | number)[]): void
}>()

const wrapperRef = ref<HTMLElement | null>(null)
const search = ref("")
const open = ref(false)

const filteredOptions = computed(() =>
    props.options.filter((opt) =>
        opt.label.toLowerCase().includes(search.value.toLowerCase())
    )
)

const selectedOptions = computed(() =>
    props.options.filter((opt) =>
        props.modelValue.includes(opt.value)
    )
)

const toggleOption = (value: string | number) => {
    const exists = props.modelValue.includes(value)

    if (exists) {
        emit(
            "update:modelValue",
            props.modelValue.filter((v) => v !== value)
        )
    } else {
        emit("update:modelValue", [...props.modelValue, value])
    }

    search.value = "" // clear search after select
}

const removeOption = (value: string | number) => {
    emit(
        "update:modelValue",
        props.modelValue.filter((v) => v !== value)
    )
}

/* ---- Click Outside Manual ---- */
const handleClickOutside = (event: MouseEvent) => {
    if (!wrapperRef.value) return

    if (!wrapperRef.value.contains(event.target as Node)) {
        open.value = false
    }
}

onMounted(() => {
    document.addEventListener("click", handleClickOutside)
})

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside)
})
</script>

<template>
    <div ref="wrapperRef" class="relative space-y-3">

        <!-- Search Input -->
        <Input v-model="search" :placeholder="placeholder ?? 'Cari dan pilih...'" @focus="open = true" />

        <!-- Dropdown -->
        <div v-if="open"
            class="absolute z-50 mt-1 w-full rounded-md border bg-background shadow-md max-h-60 overflow-y-auto animate-in fade-in zoom-in-95">
            <div v-for="option in filteredOptions" :key="option.value" @click="toggleOption(option.value)"
                class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm hover:bg-muted transition">
                <span>{{ option.label }}</span>

                <span v-if="modelValue.includes(option.value)" class="text-primary text-xs">
                    ✓
                </span>
            </div>

            <div v-if="filteredOptions.length === 0" class="px-3 py-2 text-sm text-muted-foreground">
                Tidak ditemukan.
            </div>
        </div>

        <!-- Selected Badges -->
        <div v-if="selectedOptions.length" class="flex flex-wrap gap-2">
            <Badge v-for="option in selectedOptions" :key="option.value" variant="secondary"
                class="flex items-center gap-1 px-3 py-1">
                {{ option.label }}

                <button type="button" @click.stop="removeOption(option.value)" class="hover:text-destructive">
                    <X class="h-3 w-3" />
                </button>
            </Badge>
        </div>

    </div>
</template>
