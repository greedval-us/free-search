<script setup lang="ts">
import { computed, onMounted, shallowRef } from 'vue';
import { useI18n } from '@/composables/useI18n';
import {
    getDeviceTimezone,
    REPORT_TIMEZONES,
    timezoneLabel,
} from '@/lib/report-timezones';

const props = defineProps<{
    id: string;
    labelKey: string;
    disabled?: boolean;
}>();
const timezone = defineModel<string>({ required: true });
const { t, locale } = useI18n();
const deviceTimezone = shallowRef<string | null>(null);
const displayedAt = new Date();
const helpId = computed(() => `${props.id}-help`);
const groups = computed(() =>
    (['russia', 'world'] as const).map((group) => ({
        group,
        options: REPORT_TIMEZONES.filter((item) => item.group === group).map(
            (item) => ({
                value: item.value,
                label: timezoneLabel(item.value, locale.value, t, displayedAt),
            })
        ),
    }))
);
const additionalTimezone = computed(() =>
    timezone.value &&
    !REPORT_TIMEZONES.some((item) => item.value === timezone.value)
        ? {
              value: timezone.value,
              label: timezoneLabel(
                  timezone.value,
                  locale.value,
                  t,
                  displayedAt
              ),
          }
        : null
);
onMounted(() => {
    deviceTimezone.value = getDeviceTimezone();
});
const useDeviceTimezone = () => {
    if (deviceTimezone.value) {
        timezone.value = deviceTimezone.value;
    }
};
</script>

<template>
    <div class="intel-field">
        <label :for="id" class="intel-label">{{ t(labelKey) }}</label>
        <select
            :id="id"
            v-model="timezone"
            class="intel-select"
            :aria-describedby="helpId"
            :disabled="disabled"
            required
        >
            <option v-if="additionalTimezone" :value="additionalTimezone.value">
                {{ additionalTimezone.label }}
            </option>
            <optgroup
                v-for="group in groups"
                :key="group.group"
                :label="t(`reportTimezones.groups.${group.group}`)"
            >
                <option
                    v-for="option in group.options"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </optgroup>
        </select>
        <p :id="helpId" class="text-xs text-muted-foreground">
            {{ t('reportTimezones.help') }}
        </p>
        <button
            v-if="deviceTimezone"
            type="button"
            class="intel-button-secondary self-start"
            :disabled="disabled"
            @click="useDeviceTimezone"
        >
            {{ t('reportTimezones.useDevice') }}
        </button>
    </div>
</template>
