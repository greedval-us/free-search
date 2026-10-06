<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';
import { history, index } from '@/routes/monitoring';
import { show, store } from '@/routes/monitoring/projects';
import Pagination from './Pagination.vue';
import ProjectForm from './ProjectForm.vue';
import type { Pagination as Page, Project, ProjectSettings } from './types';
import { useMonitoringState } from './useMonitoringState';
const props = defineProps<{
    projects: Page<Project>;
    defaultTimezone: string;
}>();
const { t, locale } = useI18n();
const createOpen = ref(false);
const form = ref<ProjectSettings>({
    name: '',
    mode: 'overview',
    filters: { include: [], exclude: [], author: null },
    language: locale.value,
    timezone:
        typeof Intl === 'undefined'
            ? props.defaultTimezone
            : Intl.DateTimeFormat().resolvedOptions().timeZone ||
              props.defaultTimezone,
    delivery_enabled: false,
    attach_files: false,
    empty_delivery: 'skip',
    collection_enabled: true,
    collect_interval_minutes: 360,
});
const { busy, error, mutate } = useMonitoringState(
    ref({}),
    ref(index.url()),
    () => false
);
const create = async () => {
    const result = await mutate<{ id: number }>(
        store.url(),
        'POST',
        form.value
    );

    if (!result) {
        return;
    }

    router.visit(show.url(result.id));
};
</script>
<template>
    <Head :title="t('monitoring.title')" />
    <div class="mx-auto grid w-full max-w-6xl gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="t('monitoring.title')"
                :description="t('monitoring.description')"
            />
            <div class="flex flex-wrap gap-2">
                <Button as-child variant="outline"
                    ><Link :href="history()">{{
                        t('monitoring.history')
                    }}</Link></Button
                ><Button @click="createOpen = !createOpen">{{
                    t('monitoring.create')
                }}</Button>
            </div>
        </div>
        <p
            v-if="error"
            role="alert"
            class="rounded-lg bg-destructive/10 p-4 text-destructive"
        >
            {{ error }}
        </p>
        <section
            v-if="createOpen"
            class="rounded-xl border border-sidebar-border/70 p-5"
        >
            <ProjectForm v-model="form" :busy="busy" @submit="create" />
        </section>
        <p
            v-if="!projects.data.length"
            class="rounded-xl border border-dashed p-6 text-muted-foreground"
        >
            {{ t('monitoring.firstRun') }}
        </p>
        <div class="grid gap-4 sm:grid-cols-2">
            <Link
                v-for="project in projects.data"
                :key="project.id"
                :href="show(project.id)"
                class="grid gap-3 rounded-xl border border-sidebar-border/70 p-5 hover:bg-muted/40"
            >
                <div class="flex justify-between gap-3">
                    <h2 class="font-semibold">{{ project.name }}</h2>
                    <span class="text-sm text-muted-foreground">{{
                        t(`monitoring.status.${project.status}`)
                    }}</span>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ t(`monitoring.${project.mode}`) }} ·
                    {{ project.timezone }}
                </p>
                <p class="text-sm">
                    {{ t('monitoring.sources') }}: {{ project.sources_count }} ·
                    {{ t('monitoring.reports') }}: {{ project.reports_count }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{
                        t(
                            project.delivery_enabled
                                ? 'monitoring.deliveryEnabled'
                                : 'monitoring.deliveryOff'
                        )
                    }}
                </p>
            </Link>
        </div>
        <Pagination :page="projects" />
    </div>
</template>
