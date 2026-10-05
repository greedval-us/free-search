<script setup lang="ts">
import { LoaderCircle } from 'lucide-vue-next';
import { useId } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        label: string;
        placeholder?: string;
        buttonText: string;
        loadingText?: string;
        loading?: boolean;
        disabled?: boolean;
        error?: string | null;
        inputType?: string;
    }>(),
    {
        placeholder: '',
        loadingText: '',
        loading: false,
        disabled: false,
        error: null,
        inputType: 'text',
    }
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
    submit: [];
}>();
const errorId = useId();

const onSubmit = () => {
    if (props.disabled || props.loading) {
        return;
    }

    emit('submit');
};
</script>

<template>
    <div class="mt-3 grid min-w-0 gap-3 sm:flex sm:flex-wrap sm:items-end">
        <label class="block min-w-0 sm:flex-1">
            <span class="intel-label">{{ label }}</span>
            <input
                :value="modelValue"
                :type="inputType"
                class="intel-input"
                :placeholder="placeholder"
                :aria-invalid="Boolean(error)"
                :aria-describedby="error ? errorId : undefined"
                @input="
                    emit(
                        'update:modelValue',
                        ($event.target as HTMLInputElement).value
                    )
                "
                @keydown.enter.prevent="onSubmit"
            />
        </label>

        <button
            type="button"
            :disabled="loading || disabled"
            class="intel-button-primary w-full justify-center px-5 sm:w-auto"
            @click="onSubmit"
        >
            <LoaderCircle
                v-if="loading"
                class="h-4 w-4 animate-spin"
                aria-hidden="true"
            />
            <span>{{ loading ? loadingText || buttonText : buttonText }}</span>
        </button>

        <slot name="actions" />
    </div>

    <p
        v-if="error"
        :id="errorId"
        role="alert"
        class="mt-3 rounded-xl border border-destructive/20 bg-destructive/5 px-3 py-2.5 text-sm leading-6 break-words text-destructive"
    >
        {{ error }}
    </p>
</template>
