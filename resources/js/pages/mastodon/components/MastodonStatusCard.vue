<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import { computed, useSlots } from 'vue';
import { useI18n } from '@/composables/useI18n';
import type { MastodonStatus } from '../types';

const props = withDefaults(
    defineProps<{
        status: MastodonStatus;
        formatDate: (value: string) => string;
        compact?: boolean;
        showLanguage?: boolean;
        showMediaCount?: boolean;
    }>(),
    {
        compact: false,
        showLanguage: false,
        showMediaCount: false,
    }
);

const { t } = useI18n();
const slots = useSlots();

const avatarClass = computed(() => (props.compact ? 'h-8 w-8' : 'h-10 w-10'));
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
const contentClass = computed(() =>
    props.compact
        ? 'text-xs leading-relaxed text-foreground'
        : 'text-sm leading-relaxed text-foreground'
);
const badgeClass = computed(() =>
    props.compact
        ? 'rounded-full border border-input px-2 py-1 text-[11px] text-muted-foreground'
        : 'rounded-full border border-input px-2 py-1 text-xs text-muted-foreground'
);
const domainBadgeClass = computed(() =>
    props.compact
        ? 'rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2 py-1 text-[11px] text-cyan-700'
        : 'rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2 py-1 text-xs text-cyan-700'
);
const linkButtonClass = computed(() =>
    props.compact
        ? 'cursor-pointer rounded-full border border-input px-2 py-1 text-[11px] text-primary hover:bg-accent'
        : 'cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent'
);
const mentionsClass = computed(() =>
    props.compact ? 'mt-2 space-y-2 text-[11px]' : 'mt-3 space-y-2 text-xs'
);
const metaListClass = computed(() =>
    props.compact
        ? 'mt-2 flex flex-wrap gap-3 text-[11px] text-muted-foreground'
        : 'mt-3 flex flex-wrap gap-3 text-xs text-muted-foreground'
);
const actionsClass = computed(() =>
    props.compact ? 'mt-2 flex flex-wrap gap-2' : 'mt-3 flex flex-wrap gap-2'
);

const hasActions = computed(() => Boolean(slots.actions));
const hasDetails = computed(() => Boolean(slots.details));
</script>

<template>
    <article class="rounded-md border border-border/70 bg-background/70 p-3">
        <div class="mb-2 flex items-center gap-3">
            <img
                v-if="status.account.avatar"
                :src="status.account.avatar"
                :alt="status.account.displayName || status.account.acct"
                :class="[avatarClass, 'rounded-full object-cover']"
                loading="lazy"
            />
            <div class="min-w-0">
                <div :class="titleClass">
                    {{ status.account.displayName || status.account.username }}
                </div>
                <div :class="metaClass">
                    @{{ status.account.acct }} |
                    {{ status.instanceDomain || status.account.instanceDomain }}
                    | {{ formatDate(status.createdAt) }}
                </div>
            </div>
        </div>

        <p
            v-if="status.spoilerText"
            class="mb-2 rounded-md border border-amber-500/30 bg-amber-500/10 px-2 py-1 text-[11px] text-amber-700"
        >
            {{ status.spoilerText }}
        </p>

        <p :class="contentClass">
            {{ status.content || t('mastodon.search.noText') }}
        </p>

        <div :class="metaListClass">
            <span
                >{{ t('mastodon.metrics.postType') }}:
                {{ t(`mastodon.postTypes.${status.postType}`) }}</span
            >
            <span v-if="showLanguage"
                >{{ t('mastodon.metrics.language') }}:
                {{ status.language || '-' }}</span
            >
            <span
                >{{ t('mastodon.metrics.replies') }}:
                {{ status.repliesCount }}</span
            >
            <span
                >{{ t('mastodon.metrics.reblogs') }}:
                {{ status.reblogsCount }}</span
            >
            <span
                >{{ t('mastodon.metrics.favourites') }}:
                {{ status.favouritesCount }}</span
            >
            <span v-if="showMediaCount"
                >{{ t('mastodon.metrics.media') }}:
                {{ status.mediaAttachmentsCount }}</span
            >
        </div>

        <div :class="actionsClass">
            <a
                v-if="status.url"
                :href="status.url"
                target="_blank"
                rel="noopener noreferrer"
                :class="linkButtonClass"
            >
                <ExternalLink class="mr-1 inline h-3 w-3" />
                {{ t('mastodon.common.open') }}
            </a>

            <span
                v-for="tag in status.tags"
                :key="`${status.id}-${tag}`"
                :class="badgeClass"
            >
                #{{ tag }}
            </span>

            <span
                v-for="domain in status.domains"
                :key="`${status.id}-domain-${domain}`"
                :class="domainBadgeClass"
            >
                {{ domain }}
            </span>

            <slot name="actions" />
        </div>

        <div
            v-if="status.mentions.length > 0 || status.links.length > 0"
            :class="mentionsClass"
        >
            <div v-if="status.mentions.length > 0" class="flex flex-wrap gap-2">
                <span class="text-muted-foreground"
                    >{{ t('mastodon.metrics.mentions') }}:</span
                >
                <a
                    v-for="mention in status.mentions"
                    :key="`${status.id}-mention-${mention.acct}`"
                    :href="mention.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="rounded-full border border-input px-2 py-1 text-primary hover:bg-accent"
                >
                    @{{ mention.acct }}
                </a>
            </div>

            <div v-if="status.links.length > 0" class="space-y-1">
                <div class="text-muted-foreground">
                    {{ t('mastodon.metrics.links') }}:
                </div>
                <a
                    v-for="link in status.links"
                    :key="`${status.id}-link-${link}`"
                    :href="link"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="block truncate text-primary hover:underline"
                >
                    {{ link }}
                </a>
            </div>
        </div>

        <div v-if="hasDetails" class="mt-3">
            <slot name="details" />
        </div>
    </article>
</template>
