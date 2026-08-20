<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';
import { useI18n } from '@/composables/useI18n';

const { appearance, updateAppearance } = useAppearance();
const { t } = useI18n();

const tabs = [
    { value: 'light', Icon: Sun, labelKey: 'settings.appearancePage.light' },
    { value: 'dark', Icon: Moon, labelKey: 'settings.appearancePage.dark' },
    {
        value: 'system',
        Icon: Monitor,
        labelKey: 'settings.appearancePage.system',
    },
] as const;
</script>

<template>
    <div
        class="grid w-full grid-cols-1 gap-1 rounded-lg border border-input bg-background p-1 sm:inline-flex sm:w-auto"
    >
        <button
            v-for="{ value, Icon, labelKey } in tabs"
            :key="value"
            type="button"
            @click="updateAppearance(value)"
            :class="[
                'flex min-h-10 items-center justify-center rounded-md px-3.5 py-2 transition-colors',
                appearance === value
                    ? 'bg-primary text-primary-foreground shadow-xs'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
            ]"
        >
            <component :is="Icon" class="-ml-1 h-4 w-4" />
            <span class="ml-1.5 text-sm">{{ t(labelKey) }}</span>
        </button>
    </div>
</template>
