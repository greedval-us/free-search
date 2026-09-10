<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import type { TrackingForm, TrackingList } from './types';

const props = defineProps<{
    limits: TrackingList;
    busy: boolean;
    validatedGroups: string[];
}>();
defineEmits<{ create: []; validate: [] }>();
const form = defineModel<TrackingForm>({ required: true });
const { t } = useI18n();
const canCreate = computed(
    () =>
        props.limits.active_count < props.limits.limit &&
        form.value.name.trim().length > 0 &&
        form.value.groups.trim().length > 0 &&
        (form.value.mode === 'user'
            ? /^[1-9]\d{0,18}$/.test(form.value.query.trim())
            : [...form.value.query.trim()].length >=
              props.limits.keyword_min_length)
);
</script>

<template>
    <form
        class="intel-panel-strong space-y-4 p-4 sm:p-5"
        @submit.prevent="$emit('create')"
    >
        <div class="flex flex-wrap justify-between gap-2">
            <h3 class="font-semibold">
                {{ t('telegramTracking.new') }}
            </h3>
            <span class="text-sm text-primary">{{
                t('telegramTracking.quota', {
                    used: limits.active_count,
                    limit: limits.limit,
                })
            }}</span>
        </div>
        <p class="text-sm text-muted-foreground">
            {{
                t('telegramTracking.retention', {
                    months: limits.duration_months,
                    days: limits.retention_days,
                })
            }}
        </p>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <label class="intel-field"
                ><span class="intel-label">{{
                    t('telegramTracking.name')
                }}</span
                ><input
                    v-model="form.name"
                    class="intel-input"
                    maxlength="100"
                    required
            /></label>
            <label class="intel-field"
                ><span class="intel-label">{{
                    t('telegramTracking.mode')
                }}</span
                ><select v-model="form.mode" class="intel-select">
                    <option value="keyword">
                        {{ t('telegramTracking.modes.keyword') }}
                    </option>
                    <option value="user">
                        {{ t('telegramTracking.modes.user') }}
                    </option>
                </select></label
            >
            <label class="intel-field"
                ><span class="intel-label">{{
                    t(`telegramTracking.modes.${form.mode}`)
                }}</span
                ><input
                    v-model="form.query"
                    class="intel-input"
                    :maxlength="form.mode === 'user' ? 19 : 120"
                    :minlength="
                        form.mode === 'keyword' ? limits.keyword_min_length : 1
                    "
                    :inputmode="form.mode === 'user' ? 'numeric' : 'text'"
                    required
                /><span class="text-xs text-muted-foreground">{{
                    t('telegramTracking.matchHelp', {
                        min: limits.keyword_min_length,
                    })
                }}</span></label
            >
            <label class="intel-field md:col-span-2 xl:col-span-3"
                ><span class="intel-label">{{
                    t('telegramTracking.groups', {
                        max: limits.max_sources,
                    })
                }}</span
                ><textarea
                    v-model="form.groups"
                    class="intel-input min-h-24 resize-y"
                    rows="3"
                    :placeholder="t('telegramTracking.groupsPlaceholder')"
                    required
                /><span class="text-xs text-muted-foreground">{{
                    t('telegramTracking.noJoin')
                }}</span></label
            >
        </div>
        <p
            v-if="validatedGroups.length"
            role="status"
            class="text-sm text-primary"
        >
            {{ t('telegramTracking.validated') }}:
            {{ validatedGroups.join(', ') }}
        </p>
        <label class="flex cursor-pointer items-center gap-2 text-sm"
            ><input v-model="form.notify_bot" type="checkbox" />{{
                t('telegramTracking.notifyBot')
            }}</label
        >
        <p class="text-xs text-muted-foreground">
            {{ t('telegramTracking.botHelp') }}
            <a href="/settings/telegram" class="text-primary underline">{{
                t('telegramTracking.botSettings')
            }}</a>
        </p>
        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                class="intel-button-secondary"
                :disabled="busy || !form.groups.trim()"
                @click="$emit('validate')"
            >
                {{ t('telegramTracking.validate') }}</button
            ><button
                class="intel-button-primary"
                :disabled="busy || !canCreate"
            >
                {{ t('telegramTracking.create') }}
            </button>
        </div>
    </form>
</template>
