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
    <dl class="intel-data-list">
        <div
            v-for="item in items"
            :key="`${item.label}-${item.value}`"
            class="grid min-w-0 gap-x-4 gap-y-0.5 text-sm leading-6 sm:grid-cols-[minmax(7rem,0.45fr)_minmax(0,1fr)]"
        >
            <dt class="min-w-0 break-words text-muted-foreground">
                {{ item.label }}
            </dt>
            <dd
                class="min-w-0 font-medium break-words text-foreground"
                :class="item.valueClass"
            >
                {{ item.value }}
            </dd>
        </div>
    </dl>
</template>
