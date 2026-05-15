<script setup lang="ts">
defineProps<{
    title: string;
    value: string | number;
}>();
</script>

<template>
    <div class="intel-surface">
        <p class="intel-title">{{ title }}</p>
        <p class="intel-value">{{ value }}</p>
    </div>
</template>
