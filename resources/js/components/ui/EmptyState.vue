<script setup lang="ts">
defineProps<{
    text: string;
}>();
</script>

<template>
    <div class="intel-empty break-words" role="status">
        {{ text }}
    </div>
</template>
