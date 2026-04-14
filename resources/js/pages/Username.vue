<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { USERNAME_TABS } from './username/tabs';
import type { UsernameTabValue } from './username/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Username',
                href: '/username',
            },
        ],
    },
});

const { t } = useI18n();

const activeTab = ref<UsernameTabValue>('search');

const pageTitle = computed(() => t('username.headTitle'));

const activeTabDefinition = computed(
    () => USERNAME_TABS.find((tab) => tab.key === activeTab.value) ?? USERNAME_TABS[0]
);
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <div class="flex items-center justify-center gap-1 rounded-lg bg-slate-800/80 p-1">
            <button
                v-for="tab in USERNAME_TABS"
                :key="tab.key"
                type="button"
                @click="activeTab = tab.key"
                :class="[
                    'flex items-center rounded-md px-3 py-1.5 text-xs font-medium transition-all',
                    activeTab === tab.key
                        ? 'bg-slate-700/80 text-cyan-300 shadow-sm'
                        : 'text-slate-400 hover:bg-slate-700/50 hover:text-slate-200',
                ]"
            >
                <component :is="tab.icon" class="mr-1.5 h-3.5 w-3.5" />
                {{ t(tab.labelKey) }}
            </button>
        </div>

        <component :is="activeTabDefinition.component" />
    </div>
</template>
