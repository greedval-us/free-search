<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useI18n } from '@/composables/useI18n';
import { history, index } from '@/routes/monitoring';
import Pagination from './Pagination.vue';
import ReportList from './ReportList.vue';
import { periods } from './types';
import type { Pagination as Page, Report } from './types';
const props = defineProps<{
    reports: Page<Report>;
    projects: { id: number; name: string }[];
    filters: {
        search?: string;
        project?: number;
        period?: string;
        status?: string;
    };
}>();
const { t } = useI18n();
const filters = ref({
    search: props.filters.search ?? '',
    project: props.filters.project ?? '',
    period: props.filters.period ?? '',
    status: props.filters.status ?? '',
});
const search = () =>
    router.get(history.url(), filters.value, {
        preserveState: true,
        replace: true,
    });
</script>
<template>
    <Head :title="t('monitoring.history')" />
    <div class="mx-auto grid w-full max-w-6xl gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap justify-between gap-3">
            <Heading :title="t('monitoring.history')" /><Button
                as-child
                variant="outline"
                ><Link :href="index()">{{
                    t('monitoring.projects')
                }}</Link></Button
            >
        </div>
        <form
            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5"
            @submit.prevent="search"
        >
            <Input
                v-model="filters.search"
                :aria-label="t('monitoring.search')"
                :placeholder="t('monitoring.searchProjects')"
            />
            <select
                v-model="filters.project"
                :aria-label="t('monitoring.projects')"
                class="rounded-md border border-input bg-background p-2"
            >
                <option value="">{{ t('monitoring.allProjects') }}</option>
                <option
                    v-for="project in projects"
                    :key="project.id"
                    :value="project.id"
                >
                    {{ project.name }}
                </option>
            </select>
            <select
                v-model="filters.period"
                :aria-label="t('monitoring.periodLabel')"
                class="rounded-md border border-input bg-background p-2"
            >
                <option value="">{{ t('monitoring.allPeriods') }}</option>
                <option v-for="period in periods" :key="period" :value="period">
                    {{ t(`monitoring.period.${period}`) }}
                </option>
            </select>
            <select
                v-model="filters.status"
                :aria-label="t('monitoring.state')"
                class="rounded-md border border-input bg-background p-2"
            >
                <option value="">{{ t('monitoring.allStatuses') }}</option>
                <option
                    v-for="status in [
                        'queued',
                        'building',
                        'completed',
                        'partial',
                        'empty',
                        'failed',
                        'cancelled',
                    ]"
                    :key="status"
                    :value="status"
                >
                    {{ t(`monitoring.status.${status}`) }}
                </option>
            </select>
            <Button type="submit">{{ t('monitoring.search') }}</Button>
        </form>
        <ReportList :reports="reports.data" /><Pagination :page="reports" />
    </div>
</template>
