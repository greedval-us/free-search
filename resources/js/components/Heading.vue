<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
    as?: 'h1' | 'h2' | 'h3';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
    as: 'h2',
});
</script>

<template>
    <header
        class="min-w-0"
        :class="variant === 'small' ? '' : 'mb-5 space-y-1'"
    >
        <component
            :is="as"
            :class="
                variant === 'small'
                    ? 'mb-0.5 text-base font-medium break-words'
                    : 'text-xl font-semibold tracking-tight break-words'
            "
        >
            {{ title }}
        </component>
        <p
            v-if="description"
            class="text-sm leading-6 break-words text-muted-foreground"
        >
            {{ description }}
        </p>
    </header>
</template>
