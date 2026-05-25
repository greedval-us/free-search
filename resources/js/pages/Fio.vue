<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { LoaderCircle, Search } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import IntelModuleLayout from '@/components/ui/IntelModuleLayout.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useI18n } from '@/composables/useI18n';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import FioResultView from './fio/components/FioResultView.vue';
import { useFioLookup } from './fio/composables/useFioLookup';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'FIO',
                href: '/fio',
            },
        ],
    },
});

const { t, locale } = useI18n();
const { form, loading, error, result, canLookup, lookup } = useFioLookup(
    t,
    locale
);

const pageTitle = computed(() => t('fio.headTitle'));

onMounted(() => {
    const params = getRepeatQueryParams();

    if (!params) {
        return;
    }

    const fullName = readRepeatQueryParam(params, ['full_name', 'fullName']);
    const qualifier = readRepeatQueryParam(params, ['qualifier']);

    if (fullName !== '') {
        form.fullName = fullName;
    }

    if (qualifier !== '') {
        form.qualifier = qualifier;
    }

    if (isRepeatAutorunEnabled(params) && canLookup.value) {
        void lookup();
    }
});
</script>

<template>
    <Head :title="pageTitle" />

    <IntelModuleLayout>
        <IntelSearchPanel>
            <div class="flex items-center justify-between gap-3">
                <PageHeader
                    :icon="Search"
                    :title="t('fio.lookup.title')"
                    :description="t('fio.lookup.description')"
                />
            </div>

            <div class="mt-3 flex flex-wrap items-end gap-3">
                <label class="block min-w-0 flex-1">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('fio.lookup.fullName') }}
                    </span>
                    <input
                        v-model="form.fullName"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('fio.lookup.placeholder')"
                        @keydown.enter.prevent="lookup"
                    />
                </label>
                <label class="block min-w-0 flex-1">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('fio.lookup.qualifier') }}
                    </span>
                    <input
                        v-model="form.qualifier"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('fio.lookup.qualifierPlaceholder')"
                        @keydown.enter.prevent="lookup"
                    />
                </label>

                <button
                    :disabled="loading || !canLookup"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    @click="lookup"
                >
                    <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                    <span>
                        {{
                            loading
                                ? t('fio.lookup.searching')
                                : t('fio.lookup.search')
                        }}
                    </span>
                </button>
            </div>

            <p v-if="error" class="mt-3 text-sm text-destructive">
                {{ error }}
            </p>
        </IntelSearchPanel>

        <IntelResultPanel>
            <EmptyState v-if="!result" :text="t('fio.lookup.empty')" />
            <FioResultView v-else :result="result" />
        </IntelResultPanel>
    </IntelModuleLayout>
</template>
