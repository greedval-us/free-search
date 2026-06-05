<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import { computed, useSlots } from 'vue';
import { useI18n } from '@/composables/useI18n';
import type { MastodonAccount } from '../types';

const props = withDefaults(
    defineProps<{
        account: MastodonAccount;
        formatDate: (value: string) => string;
        compact?: boolean;
        showFields?: boolean;
        showOpenProfile?: boolean;
    }>(),
    {
        compact: false,
        showFields: true,
        showOpenProfile: true,
    }
);

const { t } = useI18n();
const slots = useSlots();

const avatarClass = computed(() => (props.compact ? 'h-10 w-10' : 'h-12 w-12'));
const titleClass = computed(() =>
    props.compact
        ? 'truncate text-xs font-semibold text-foreground'
        : 'truncate text-sm font-semibold'
);
const metaClass = computed(() =>
    props.compact
        ? 'truncate text-[11px] text-muted-foreground'
        : 'truncate text-xs text-muted-foreground'
);
const noteClass = computed(() =>
    props.compact
        ? 'mt-2 text-xs leading-relaxed text-foreground'
        : 'mt-2 text-sm leading-relaxed text-foreground'
);
const statsClass = computed(() =>
    props.compact
        ? 'mt-2 flex flex-wrap gap-3 text-[11px] text-muted-foreground'
        : 'mt-3 flex flex-wrap gap-3 text-xs text-muted-foreground'
);
const fieldsClass = computed(() =>
    props.compact
        ? 'mt-2 grid gap-2 md:grid-cols-2'
        : 'mt-3 grid gap-2 md:grid-cols-2'
);
const actionsClass = computed(() =>
    props.compact ? 'mt-2 flex flex-wrap gap-2' : 'mt-3 flex flex-wrap gap-2'
);

const hasActions = computed(() => Boolean(slots.actions));
const hasDetails = computed(() => Boolean(slots.details));
</script>

<template>
    <article class="intel-result-card">
        <div class="flex items-start gap-3">
            <img
                v-if="account.avatar"
                :src="account.avatar"
                :alt="account.displayName || account.acct"
                :class="[avatarClass, 'rounded-full object-cover']"
                loading="lazy"
            />
            <div class="min-w-0 flex-1">
                <div :class="titleClass">
                    {{ account.displayName || account.username }}
                </div>
                <div :class="metaClass">
                    @{{ account.acct }} | {{ account.instanceDomain }} |
                    {{ formatDate(account.createdAt) }}
                </div>
                <p :class="noteClass">
                    {{ account.note || t('mastodon.search.noBio') }}
                </p>
                <div :class="statsClass">
                    <span
                        >{{ t('mastodon.metrics.followers') }}:
                        {{ account.followersCount }}</span
                    >
                    <span
                        >{{ t('mastodon.metrics.following') }}:
                        {{ account.followingCount }}</span
                    >
                    <span
                        >{{ t('mastodon.metrics.posts') }}:
                        {{ account.statusesCount }}</span
                    >
                    <span v-if="account.bot">{{
                        t('mastodon.metrics.bot')
                    }}</span>
                    <span v-if="account.group">{{
                        t('mastodon.metrics.group')
                    }}</span>
                </div>
                <div
                    v-if="showFields && account.fields.length > 0"
                    :class="fieldsClass"
                >
                    <div
                        v-for="field in account.fields"
                        :key="`${account.id}-${field.name}-${field.value}`"
                        class="intel-stat"
                    >
                        <div class="font-medium text-foreground">
                            {{ field.name }}
                        </div>
                        <div class="break-all text-muted-foreground">
                            {{ field.value }}
                        </div>
                    </div>
                </div>
                <div v-if="showOpenProfile || hasActions" :class="actionsClass">
                    <slot name="actions" />

                    <a
                        v-if="showOpenProfile && account.url"
                        :href="account.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        :class="
                            compact
                                ? 'intel-link-pill text-[11px]'
                                : 'intel-link-pill'
                        "
                    >
                        <ExternalLink class="mr-1 inline h-3 w-3" />
                        {{ t('mastodon.common.openProfile') }}
                    </a>
                </div>

                <div v-if="hasDetails" class="mt-3">
                    <slot name="details" />
                </div>
            </div>
        </div>
    </article>
</template>
