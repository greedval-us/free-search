<script setup lang="ts">
type KeyValueItem = {
    label: string;
    value: string | number;
    valueClass?: string;
};

defineProps<{
    items: KeyValueItem[];
}>();
</script>

<template>
    <div class="intel-data-list">
        <div
            v-for="item in items"
            :key="`${item.label}-${item.value}`"
            class="intel-data-row"
        >
            <span class="intel-data-label">{{ item.label }}:</span>
            <span class="intel-data-value" :class="item.valueClass">
                {{ item.value }}
            </span>
        </div>
    </div>
</template>
