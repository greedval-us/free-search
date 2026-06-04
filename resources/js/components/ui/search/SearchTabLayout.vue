<script setup lang="ts">
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import SearchControlPanel from './SearchControlPanel.vue';

defineProps<{
    title: string;
    helpLabel: string;
    helpText: string;
    subtitle: string;
    collapsedText: string;
    collapsed: boolean;
    showAdvanced: boolean;
    loading: boolean;
    canSearch: boolean;
    advancedShowAria: string;
    advancedHideAria: string;
    submitLabel: string;
    searchingLabel: string;
    error?: string | null;
}>();

const emit = defineEmits<{
    'update:collapsed': [value: boolean];
    'update:showAdvanced': [value: boolean];
    submit: [];
}>();
</script>

<template>
    <SearchControlPanel
        :title="title"
        :help-label="helpLabel"
        :help-text="helpText"
        :subtitle="subtitle"
        :collapsed-text="collapsedText"
        :collapsed="collapsed"
        :show-advanced="showAdvanced"
        :loading="loading"
        :can-search="canSearch"
        :advanced-show-aria="advancedShowAria"
        :advanced-hide-aria="advancedHideAria"
        :submit-label="submitLabel"
        :searching-label="searchingLabel"
        @update:collapsed="emit('update:collapsed', $event)"
        @update:show-advanced="emit('update:showAdvanced', $event)"
        @submit="emit('submit')"
    >
        <template #fields>
            <slot name="fields" />
        </template>
        <template #toolbarLeading>
            <slot name="toolbarLeading" />
        </template>
        <template #advanced>
            <slot name="advanced" />
        </template>
        <template #afterActions>
            <p v-if="error" class="mt-3 text-sm text-destructive">
                {{ error }}
            </p>
            <slot name="afterActions" />
        </template>
    </SearchControlPanel>

    <IntelResultPanel>
        <slot name="results" />
        <slot name="footer" />
    </IntelResultPanel>
</template>
