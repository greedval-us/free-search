<script setup lang="ts">
defineProps<{
    text: string;
}>();
</script>

<template>
    <div class="intel-empty">
        {{ text }}
    </div>
</template>
