<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 48 48"
        :class="className"
        v-bind="$attrs"
    >
        <circle cx="24" cy="24" r="16" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-dasharray="82 28" transform="rotate(-36 24 24)" />
        <path d="M33.8 12.9 39.2 14.2 35.2 18.1" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" />
        <circle cx="16.6" cy="33.8" r="2.8" fill="currentColor" />
    </svg>
</template>
