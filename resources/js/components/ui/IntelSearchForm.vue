<script setup lang="ts">
import { LoaderCircle } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
    modelValue: string;
    label: string;
    placeholder?: string;
    buttonText: string;
    loadingText?: string;
    loading?: boolean;
    disabled?: boolean;
    error?: string | null;
    inputType?: string;
}>(), {
    placeholder: '',
    loadingText: '',
    loading: false,
    disabled: false,
    error: null,
    inputType: 'text',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    submit: [];
}>();

const onSubmit = () => {
    if (props.disabled) {
        return;
    }

    emit('submit');
};
</script>

<template>
    <div class="mt-3 flex flex-wrap items-end gap-3">
        <label class="block min-w-0 flex-1">
            <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ label }}</span>
            <input
                :value="modelValue"
                :type="inputType"
                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                :placeholder="placeholder"
                @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
                @keydown.enter.prevent="onSubmit"
            />
        </label>

        <button
            :disabled="loading || disabled"
            class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
            @click="onSubmit"
        >
            <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
            <span>{{ loading ? loadingText || buttonText : buttonText }}</span>
        </button>

        <slot name="actions" />
    </div>

    <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
</template>
