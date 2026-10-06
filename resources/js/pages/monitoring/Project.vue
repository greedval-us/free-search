<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, toRaw, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/composables/useI18n';
import { history, index, materials } from '@/routes/monitoring';
import {
    destroy,
    lifecycle,
    status,
    update,
} from '@/routes/monitoring/projects';
import {
    show as reportShow,
    store as reportStore,
} from '@/routes/monitoring/reports';
import {
    store as scheduleStore,
    update as scheduleUpdate,
} from '@/routes/monitoring/schedules';
import {
    destroy as sourceDestroy,
    store as sourceStore,
    validate as sourceValidate,
} from '@/routes/monitoring/sources';
import { settings as telegramSettings } from '@/routes/telegram-bot';
import { displayDate, scheduleDescription, sourceIssue } from './presentation';
import ProjectForm from './ProjectForm.vue';
import ReportList from './ReportList.vue';
import { periods, platforms } from './types';
import type { Period, ProjectSettings, ProjectState, Schedule } from './types';
import { useMonitoringState } from './useMonitoringState';
const props = defineProps<ProjectState>();
const { t, locale } = useI18n();
const { state, busy, error, mutate, refresh } = useMonitoringState(
    computed(() => ({
        project: props.project,
        reports: props.reports,
        botLinked: props.botLinked,
        limits: props.limits,
    })),
    computed(() => status.url(props.project.id)),
    (s) =>
        s.project.status === 'active' ||
        s.reports.some((r) => ['queued', 'building'].includes(r.status))
);
const settingsOpen = ref(false);
const settings = ref<ProjectSettings>(structuredClone(toRaw(props.project)));
const source = ref({ platform: platforms[0], input: '' });
const reportPeriod = ref<Period>('day');
const requestKey = ref(crypto.randomUUID());
const schedule = ref({
    period: 'day' as Period,
    enabled: true,
    time: '09:00',
    timezone: props.project.timezone,
    anchor_date: new Date().toISOString().slice(0, 10),
    delivery_enabled: props.project.delivery_enabled,
});
const editingSchedule = ref<number | null>(null);
const deleteConfirmation = ref('');
watch(
    () => props.project.id,
    () => {
        settings.value = structuredClone(toRaw(props.project));
        settingsOpen.value = false;
        editingSchedule.value = null;
        deleteConfirmation.value = '';
        source.value.input = '';
    }
);
const act = async (
    url: string,
    method: 'POST' | 'PATCH' | 'DELETE',
    body?: unknown
) => {
    await mutate(url, method, body, refresh);
};
const saveSettings = async () => {
    await act(update.url(props.project.id), 'PATCH', settings.value);

    if (!error.value) {
        settingsOpen.value = false;
    }
};
const addSource = async () => {
    await act(sourceStore.url(props.project.id), 'POST', source.value);

    if (!error.value) {
        source.value.input = '';
    }
};
const runReport = async () => {
    const result = await mutate<{ id: number }>(
        reportStore.url(props.project.id),
        'POST',
        { period: reportPeriod.value, request_key: requestKey.value }
    );

    if (result) {
        router.visit(reportShow.url(result.id));
    }
};
const editSchedule = (value: Schedule) => {
    editingSchedule.value = value.id;
    schedule.value = {
        period: value.period,
        enabled: value.enabled,
        time: value.time.slice(0, 5),
        timezone: value.timezone,
        anchor_date:
            value.anchor_date?.slice(0, 10) ??
            new Date().toISOString().slice(0, 10),
        delivery_enabled: value.delivery_enabled,
    };
};
const saveSchedule = async () => {
    await act(
        editingSchedule.value
            ? scheduleUpdate.url([props.project.id, editingSchedule.value])
            : scheduleStore.url(props.project.id),
        editingSchedule.value ? 'PATCH' : 'POST',
        schedule.value
    );

    if (!error.value) {
        editingSchedule.value = null;
    }
};
const remove = async () => {
    if (
        deleteConfirmation.value === state.value.project.name &&
        (await mutate(destroy.url(props.project.id), 'DELETE', {
            confirmation: 'delete',
        }))
    ) {
        router.visit(index.url());
    }
};
</script>
<template>
    <Head :title="state.project.name" />
    <div class="mx-auto grid w-full max-w-6xl gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="state.project.name"
                :description="t(`monitoring.status.${state.project.status}`)"
            />
            <div class="flex flex-wrap gap-2">
                <Button as-child variant="outline"
                    ><Link :href="index()">{{
                        t('monitoring.projects')
                    }}</Link></Button
                ><Button @click="settingsOpen = !settingsOpen">{{
                    t('monitoring.settings')
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
        <p
            v-if="state.project.status !== 'active'"
            class="rounded-lg bg-muted p-4"
        >
            {{ t('monitoring.pausedHint') }}
        </p>
        <p class="text-sm text-muted-foreground">
            {{
                t('monitoring.planLimits', {
                    sources: state.limits.sources,
                    interval: state.limits.min_interval_minutes,
                    days: state.limits.retention_days,
                })
            }}
        </p>
        <div class="flex flex-wrap gap-2">
            <Button
                :disabled="busy"
                variant="outline"
                @click="
                    act(lifecycle.url(project.id), 'POST', {
                        status:
                            state.project.status === 'active'
                                ? 'paused'
                                : 'active',
                    })
                "
                >{{
                    t(
                        state.project.status === 'active'
                            ? 'monitoring.pause'
                            : 'monitoring.resume'
                    )
                }}</Button
            >
            <Button
                v-if="state.project.status !== 'archived'"
                :disabled="busy"
                variant="outline"
                @click="
                    act(lifecycle.url(project.id), 'POST', {
                        status: 'archived',
                    })
                "
                >{{ t('monitoring.archive') }}</Button
            >
            <Button as-child variant="outline"
                ><Link :href="materials(project.id)">{{
                    t('monitoring.materials')
                }}</Link></Button
            >
            <Button as-child variant="outline"
                ><Link :href="history({ query: { project: project.id } })">{{
                    t('monitoring.history')
                }}</Link></Button
            >
        </div>
        <section
            v-if="settingsOpen"
            class="rounded-xl border border-sidebar-border/70 p-5"
        >
            <ProjectForm
                v-model="settings"
                :busy="busy"
                @submit="saveSettings"
            />
        </section>
        <section
            class="grid gap-3 rounded-xl border border-sidebar-border/70 p-5"
        >
            <h2 class="font-semibold">{{ t('monitoring.delivery') }}</h2>
            <p class="text-sm">
                {{
                    t(
                        !state.botLinked
                            ? 'monitoring.botNotLinked'
                            : !state.project.delivery_enabled
                              ? 'monitoring.deliveryOff'
                              : 'monitoring.botLinked'
                    )
                }}
            </p>
            <Button as-child variant="outline" class="justify-self-start"
                ><Link :href="telegramSettings()">{{
                    t('monitoring.botSettings')
                }}</Link></Button
            >
        </section>
        <section
            class="grid gap-4 rounded-xl border border-sidebar-border/70 p-5"
        >
            <h2 class="font-semibold">{{ t('monitoring.sources') }}</h2>
            <p
                v-if="!state.project.sources.length"
                class="text-sm text-muted-foreground"
            >
                {{ t('monitoring.noSources') }}
            </p>
            <article
                v-for="item in state.project.sources"
                :key="item.id"
                class="grid gap-2 rounded-lg bg-muted/30 p-4"
            >
                <div class="flex flex-wrap justify-between gap-2">
                    <strong class="break-all"
                        >{{ t(`monitoring.platform.${item.platform}`) }} ·
                        {{ item.title ?? item.input }}</strong
                    ><span class="text-sm">{{
                        t(`monitoring.status.${item.status}`)
                    }}</span>
                </div>
                <p class="font-mono text-xs break-all">
                    {{ item.identity ?? t('monitoring.pendingIdentity') }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ t('monitoring.lastCollection') }}:
                    {{
                        displayDate(
                            item.last_collected_at,
                            state.project.timezone,
                            locale
                        )
                    }}
                    · {{ t('monitoring.nextCollection') }}:
                    {{
                        displayDate(
                            item.next_collect_at,
                            state.project.timezone,
                            locale
                        )
                    }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ t('monitoring.coverage') }}:
                    {{
                        displayDate(
                            item.coverage_start,
                            state.project.timezone,
                            locale
                        )
                    }}
                    →
                    {{
                        displayDate(
                            item.coverage_end,
                            state.project.timezone,
                            locale
                        )
                    }}
                </p>
                <p
                    v-if="item.error"
                    role="alert"
                    class="text-sm text-destructive"
                >
                    {{ sourceIssue(item.error, t) }}
                </p>
                <p
                    v-for="(warning, i) in item.warnings"
                    :key="i"
                    class="text-sm text-muted-foreground"
                >
                    {{ sourceIssue(warning, t) }}
                </p>
                <div class="flex gap-2">
                    <Button
                        :disabled="busy"
                        variant="outline"
                        @click="
                            act(
                                sourceValidate.url([project.id, item.id]),
                                'POST'
                            )
                        "
                        >{{ t('monitoring.validate') }}</Button
                    ><Button
                        :disabled="busy"
                        variant="ghost"
                        @click="
                            act(
                                sourceDestroy.url([project.id, item.id]),
                                'DELETE'
                            )
                        "
                        >{{ t('monitoring.removeSource') }}</Button
                    >
                </div>
            </article>
            <form class="grid gap-3" @submit.prevent="addSource">
                <div class="grid gap-3 sm:grid-cols-[12rem_1fr_auto]">
                    <select
                        v-model="source.platform"
                        :aria-label="t('monitoring.platformLabel')"
                        class="rounded-md border border-input bg-background p-2"
                    >
                        <option
                            v-for="platform in platforms"
                            :key="platform"
                            :value="platform"
                        >
                            {{ t(`monitoring.platform.${platform}`) }}
                        </option></select
                    ><Input
                        v-model="source.input"
                        required
                        :placeholder="
                            t(`monitoring.sourceHint.${source.platform}`)
                        "
                    /><Button
                        type="submit"
                        :disabled="busy || state.project.status === 'archived'"
                        >{{ t('monitoring.addSource') }}</Button
                    >
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ t(`monitoring.sourceInfo.${source.platform}`) }}
                </p>
            </form>
        </section>
        <section
            class="grid gap-4 rounded-xl border border-sidebar-border/70 p-5"
        >
            <h2 class="font-semibold">{{ t('monitoring.schedules') }}</h2>
            <article
                v-for="item in state.project.schedules"
                :key="item.id"
                class="grid gap-2 rounded-lg bg-muted/30 p-4"
            >
                <div class="flex flex-wrap justify-between gap-3">
                    <span>{{
                        scheduleDescription(
                            item.period,
                            item.time,
                            item.timezone,
                            item.anchor_date,
                            t
                        )
                    }}</span
                    ><span class="text-sm">{{
                        t(
                            item.enabled
                                ? 'monitoring.enabled'
                                : 'monitoring.disabled'
                        )
                    }}</span>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ t('monitoring.nextReport') }}:
                    {{ displayDate(item.next_run_at, item.timezone, locale) }} ·
                    {{
                        t(
                            item.delivery_enabled
                                ? 'monitoring.deliveryEnabled'
                                : 'monitoring.deliveryOff'
                        )
                    }}
                </p>
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        :disabled="busy"
                        @click="editSchedule(item)"
                        >{{ t('monitoring.edit') }}</Button
                    ><Button
                        variant="ghost"
                        :disabled="busy"
                        @click="
                            act(
                                scheduleUpdate.url([project.id, item.id]),
                                'PATCH',
                                {
                                    ...item,
                                    time: item.time.slice(0, 5),
                                    enabled: !item.enabled,
                                }
                            )
                        "
                        >{{
                            t(
                                item.enabled
                                    ? 'monitoring.disable'
                                    : 'monitoring.enable'
                            )
                        }}</Button
                    >
                </div>
            </article>
            <form class="grid gap-4" @submit.prevent="saveSchedule">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="schedule-period">{{
                            t('monitoring.periodLabel')
                        }}</Label
                        ><select
                            id="schedule-period"
                            v-model="schedule.period"
                            class="rounded-md border border-input bg-background p-2"
                        >
                            <option
                                v-for="period in periods"
                                :key="period"
                                :value="period"
                            >
                                {{ t(`monitoring.period.${period}`) }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="schedule-time">{{
                            t('monitoring.time')
                        }}</Label
                        ><Input
                            id="schedule-time"
                            v-model="schedule.time"
                            type="time"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="schedule-timezone">{{
                            t('monitoring.timezone')
                        }}</Label
                        ><Input
                            id="schedule-timezone"
                            v-model="schedule.timezone"
                            required
                        />
                    </div>
                </div>
                <div
                    v-if="schedule.period === 'three_days'"
                    class="grid gap-2 sm:max-w-xs"
                >
                    <Label for="schedule-anchor">{{
                        t('monitoring.anchor')
                    }}</Label
                    ><Input
                        id="schedule-anchor"
                        v-model="schedule.anchor_date"
                        type="date"
                        required
                    />
                </div>
                <label class="flex items-center gap-3"
                    ><input
                        v-model="schedule.delivery_enabled"
                        type="checkbox"
                        class="accent-primary"
                    />{{ t('monitoring.deliveryEnabled') }}</label
                >
                <p class="text-sm text-muted-foreground">
                    {{
                        scheduleDescription(
                            schedule.period,
                            schedule.time,
                            schedule.timezone,
                            schedule.anchor_date,
                            t
                        )
                    }}
                </p>
                <div class="flex gap-2">
                    <Button type="submit" :disabled="busy">{{
                        t(
                            editingSchedule
                                ? 'monitoring.save'
                                : 'monitoring.addSchedule'
                        )
                    }}</Button
                    ><Button
                        v-if="editingSchedule"
                        variant="ghost"
                        @click="editingSchedule = null"
                        >{{ t('monitoring.cancel') }}</Button
                    >
                </div>
            </form>
        </section>
        <section
            class="grid gap-4 rounded-xl border border-sidebar-border/70 p-5"
        >
            <h2 class="font-semibold">{{ t('monitoring.reports') }}</h2>
            <div class="flex flex-wrap gap-3">
                <select
                    v-model="reportPeriod"
                    :aria-label="t('monitoring.periodLabel')"
                    class="rounded-md border border-input bg-background p-2"
                >
                    <option
                        v-for="period in periods"
                        :key="period"
                        :value="period"
                    >
                        {{ t(`monitoring.period.${period}`) }}
                    </option></select
                ><Button
                    :disabled="busy || state.project.status === 'archived'"
                    @click="runReport"
                    >{{ t('monitoring.generate') }}</Button
                >
            </div>
            <ReportList :reports="state.reports" />
        </section>
        <details class="rounded-xl border border-destructive/30 p-5">
            <summary class="cursor-pointer text-sm text-destructive">
                {{ t('monitoring.delete') }}
            </summary>
            <div class="mt-4 grid gap-3">
                <p class="text-sm">
                    {{
                        t('monitoring.deleteWarning', {
                            name: state.project.name,
                        })
                    }}
                </p>
                <Input
                    v-model="deleteConfirmation"
                    :aria-label="t('monitoring.confirmDelete')"
                /><Button
                    variant="destructive"
                    :disabled="
                        busy || deleteConfirmation !== state.project.name
                    "
                    class="justify-self-start"
                    @click="remove"
                    >{{ t('monitoring.confirmDelete') }}</Button
                >
            </div>
        </details>
    </div>
</template>
