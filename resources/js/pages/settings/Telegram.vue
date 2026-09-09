<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import SettingsHero from '@/components/settings/SettingsHero.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/composables/useI18n';
import { useTelegramBotSettings } from '@/composables/useTelegramBotSettings';
import type {
    TelegramBotPreferences,
    TelegramBotState,
} from '@/types/telegramBot';

const props = defineProps<{ telegram: TelegramBotState }>();
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Telegram',
                titleKey: 'telegramBot.title',
                href: '/settings/telegram',
            },
        ],
    },
});
const { t } = useI18n();
const {
    state,
    linkUrl,
    busy,
    error,
    refresh,
    issue,
    confirm,
    disconnect,
    save,
} = useTelegramBotSettings(props.telegram);
const confirmDisconnect = ref(false);
const preferences = ref<TelegramBotPreferences>({
    locale: 'en',
    notifications_enabled: true,
    exports_enabled: false,
    broadcasts_enabled: false,
});
watch(
    () => state.value.link,
    (link) => {
        if (link) {
            preferences.value = {
                locale: link.locale,
                notifications_enabled: link.notifications_enabled,
                exports_enabled: link.exports_enabled,
                broadcasts_enabled: link.broadcasts_enabled,
            };
        }

        confirmDisconnect.value = false;
    },
    { immediate: true }
);
</script>

<template>
    <Head :title="t('telegramBot.title')" />
    <div class="max-w-4xl space-y-6">
        <SettingsHero
            :badge="t('telegramBot.badge')"
            :title="t('telegramBot.title')"
            :description="t('telegramBot.description')"
        >
            <template #summary>
                <p class="text-sm text-muted-foreground">
                    {{ t('telegramBot.status') }}
                </p>
                <p class="mt-2 text-xl font-semibold">
                    {{
                        t(
                            state.link
                                ? 'telegramBot.linked'
                                : 'telegramBot.notLinked'
                        )
                    }}
                </p>
                <p v-if="state.link" class="mt-2 font-mono text-sm break-all">
                    ID {{ state.link.telegram_id }}
                </p>
            </template>
        </SettingsHero>

        <p
            v-if="error"
            role="alert"
            class="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive"
        >
            {{ error }}
        </p>
        <section
            v-if="!state.available"
            class="rounded-xl border border-sidebar-border/70 bg-background/30 p-5"
        >
            <Heading
                variant="small"
                :title="t('telegramBot.unavailableTitle')"
                :description="t('telegramBot.unavailable')"
            />
        </section>

        <section
            v-if="!state.link && state.available"
            class="space-y-5 rounded-xl border border-sidebar-border/70 bg-background/30 p-5 sm:p-6"
        >
            <Heading
                variant="small"
                :title="t('telegramBot.connectTitle')"
                :description="t('telegramBot.connectDescription')"
            />
            <ol
                class="list-decimal space-y-2 pl-5 text-sm leading-6 text-muted-foreground"
            >
                <li>{{ t('telegramBot.stepOne') }}</li>
                <li>{{ t('telegramBot.stepTwo') }}</li>
                <li>{{ t('telegramBot.stepThree') }}</li>
            </ol>
            <div class="flex flex-wrap gap-3">
                <Button :disabled="busy" @click="issue">{{
                    t(
                        state.pending
                            ? 'telegramBot.regenerate'
                            : 'telegramBot.createLink'
                    )
                }}</Button>
                <Button v-if="linkUrl" as-child variant="outline"
                    ><a
                        :href="linkUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        >{{ t('telegramBot.openBot') }}</a
                    ></Button
                >
                <Button
                    v-if="state.pending"
                    :disabled="busy"
                    variant="ghost"
                    @click="refresh"
                    >{{ t('telegramBot.refresh') }}</Button
                >
            </div>
            <div
                v-if="state.pending"
                class="space-y-3 rounded-xl border border-sidebar-border/70 p-4"
                aria-live="polite"
            >
                <template v-if="state.pending.telegram_id">
                    <p class="text-sm">
                        {{ t('telegramBot.confirmDescription') }}
                    </p>
                    <p class="font-mono text-lg font-semibold break-all">
                        {{ state.pending.telegram_id }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ t('telegramBot.confirmWarning') }}
                    </p>
                    <Button :disabled="busy" @click="confirm">{{
                        t('telegramBot.confirm')
                    }}</Button>
                </template>
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('telegramBot.waiting') }}
                </p>
            </div>
        </section>

        <section
            v-if="state.link"
            class="space-y-5 rounded-xl border border-sidebar-border/70 bg-background/30 p-5 sm:p-6"
        >
            <Heading
                variant="small"
                :title="t('telegramBot.preferences')"
                :description="t('telegramBot.preferencesDescription')"
            />
            <form class="space-y-5" @submit.prevent="save(preferences)">
                <div class="grid gap-2 sm:max-w-xs">
                    <Label for="bot-locale">{{
                        t('telegramBot.locale')
                    }}</Label>
                    <select
                        id="bot-locale"
                        v-model="preferences.locale"
                        class="h-10 cursor-pointer rounded-md border border-input bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <option value="ru">Русский</option>
                        <option value="en">English</option>
                    </select>
                </div>
                <label
                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-sidebar-border/60 p-4"
                >
                    <input
                        v-model="preferences.notifications_enabled"
                        type="checkbox"
                        class="mt-1 h-4 w-4 cursor-pointer accent-primary"
                    />
                    <span
                        ><span class="block font-medium">{{
                            t('telegramBot.notifications')
                        }}</span
                        ><span
                            class="mt-1 block text-sm text-muted-foreground"
                            >{{ t('telegramBot.notificationsHint') }}</span
                        ></span
                    >
                </label>
                <label
                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-sidebar-border/60 p-4"
                >
                    <input
                        v-model="preferences.exports_enabled"
                        type="checkbox"
                        class="mt-1 h-4 w-4 cursor-pointer accent-primary"
                    />
                    <span
                        ><span class="block font-medium">{{
                            t('telegramBot.exports')
                        }}</span
                        ><span
                            class="mt-1 block text-sm text-muted-foreground"
                            >{{ t('telegramBot.exportsHint') }}</span
                        ></span
                    >
                </label>
                <label
                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-sidebar-border/60 p-4"
                >
                    <input
                        v-model="preferences.broadcasts_enabled"
                        type="checkbox"
                        class="mt-1 h-4 w-4 cursor-pointer accent-primary"
                    />
                    <span
                        ><span class="block font-medium">{{
                            t('telegramBot.broadcasts')
                        }}</span
                        ><span
                            class="mt-1 block text-sm text-muted-foreground"
                            >{{ t('telegramBot.broadcastsHint') }}</span
                        ></span
                    >
                </label>
                <p class="text-sm leading-6 text-muted-foreground">
                    {{ t('telegramBot.privacy') }}
                </p>
                <Button :disabled="busy" type="submit">{{
                    t('telegramBot.save')
                }}</Button>
            </form>
            <div class="border-t border-sidebar-border/70 pt-5">
                <template v-if="confirmDisconnect">
                    <p class="mb-3 text-sm">
                        {{ t('telegramBot.disconnectWarning') }}
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <Button
                            :disabled="busy"
                            variant="destructive"
                            @click="disconnect"
                            >{{ t('telegramBot.confirmDisconnect') }}</Button
                        >
                        <Button
                            :disabled="busy"
                            variant="ghost"
                            @click="confirmDisconnect = false"
                            >{{ t('telegramBot.cancel') }}</Button
                        >
                    </div>
                </template>
                <Button
                    v-else
                    :disabled="busy"
                    variant="outline"
                    @click="confirmDisconnect = true"
                    >{{ t('telegramBot.disconnect') }}</Button
                >
            </div>
        </section>
        <p class="text-sm leading-6 text-muted-foreground">
            {{ t('telegramBot.webappHint') }}
        </p>
    </div>
</template>
