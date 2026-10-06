<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/composables/useI18n';
import type { ProjectSettings } from './types';
const model = defineModel<ProjectSettings>({ required: true });
defineProps<{ busy: boolean }>();
defineEmits<{ submit: [] }>();
const { t } = useI18n();
const include = computed({
    get: () => model.value.filters.include.join('\n'),
    set: (v: string) =>
        (model.value.filters.include = v
            .split(/\r?\n/)
            .map((x) => x.trim())
            .filter(Boolean)),
});
const exclude = computed({
    get: () => model.value.filters.exclude.join('\n'),
    set: (v: string) =>
        (model.value.filters.exclude = v
            .split(/\r?\n/)
            .map((x) => x.trim())
            .filter(Boolean)),
});
const author = computed({
    get: () => model.value.filters.author ?? '',
    set: (value: string | number) =>
        (model.value.filters.author = String(value).trim() || null),
});
</script>
<template>
    <form class="grid gap-5" @submit.prevent="$emit('submit')">
        <div class="grid gap-2">
            <Label for="project-name">{{ t('monitoring.name') }}</Label
            ><Input
                id="project-name"
                v-model="model.name"
                required
                maxlength="100"
            />
        </div>
        <div class="grid gap-2">
            <Label for="project-mode">{{ t('monitoring.mode') }}</Label
            ><select
                id="project-mode"
                v-model="model.mode"
                class="rounded-md border border-input bg-background p-2"
            >
                <option value="overview">{{ t('monitoring.overview') }}</option>
                <option value="topic">{{ t('monitoring.topic') }}</option>
            </select>
        </div>
        <div v-if="model.mode === 'topic'" class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="include">{{ t('monitoring.include') }}</Label
                ><textarea
                    id="include"
                    v-model="include"
                    required
                    rows="3"
                    class="rounded-md border border-input bg-background p-3"
                />
            </div>
            <div class="grid gap-2">
                <Label for="exclude">{{ t('monitoring.exclude') }}</Label
                ><textarea
                    id="exclude"
                    v-model="exclude"
                    rows="3"
                    class="rounded-md border border-input bg-background p-3"
                />
            </div>
        </div>
        <div class="grid gap-2">
            <Label for="author">{{ t('monitoring.author') }}</Label
            ><Input
                id="author"
                v-model="author"
                :placeholder="t('monitoring.authorHint')"
            />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="language">{{ t('monitoring.language') }}</Label
                ><select
                    id="language"
                    v-model="model.language"
                    class="rounded-md border border-input bg-background p-2"
                >
                    <option value="ru">{{ t('monitoring.russian') }}</option>
                    <option value="en">{{ t('monitoring.english') }}</option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="timezone">{{ t('monitoring.timezone') }}</Label
                ><Input
                    id="timezone"
                    v-model="model.timezone"
                    required
                    :placeholder="t('monitoring.timezoneExample')"
                />
            </div>
        </div>
        <div class="grid gap-2">
            <Label for="collection-interval">{{
                t('monitoring.interval')
            }}</Label
            ><Input
                id="collection-interval"
                v-model.number="model.collect_interval_minutes"
                type="number"
                min="15"
                max="10080"
                required
            />
        </div>
        <label class="flex items-center gap-3"
            ><input
                v-model="model.collection_enabled"
                type="checkbox"
                class="accent-primary"
            />{{ t('monitoring.collectionEnabled') }}</label
        >
        <label class="flex items-center gap-3"
            ><input
                v-model="model.delivery_enabled"
                type="checkbox"
                class="accent-primary"
            />{{ t('monitoring.deliveryEnabled') }}</label
        >
        <label class="flex items-center gap-3"
            ><input
                v-model="model.attach_files"
                type="checkbox"
                class="accent-primary"
            />{{ t('monitoring.attachFiles') }}</label
        >
        <div class="grid gap-2">
            <Label for="empty-delivery">{{
                t('monitoring.emptyDelivery')
            }}</Label
            ><select
                id="empty-delivery"
                v-model="model.empty_delivery"
                class="rounded-md border border-input bg-background p-2"
            >
                <option value="skip">{{ t('monitoring.skipEmpty') }}</option>
                <option value="send">{{ t('monitoring.sendEmpty') }}</option>
            </select>
        </div>
        <p class="text-sm text-muted-foreground">
            {{ t('monitoring.settingsHint') }}
        </p>
        <Button type="submit" :disabled="busy" class="justify-self-start">{{
            t(busy ? 'monitoring.saving' : 'monitoring.save')
        }}</Button>
    </form>
</template>
