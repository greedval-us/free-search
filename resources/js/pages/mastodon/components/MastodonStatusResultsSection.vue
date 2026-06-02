<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
import MastodonReplyThread from './MastodonReplyThread.vue';
import type { MastodonStatus, MastodonStatusContextState } from '../types';

defineProps<{
    statuses: MastodonStatus[];
    formatDate: (value: string) => string;
    ensureContextState: (statusId: string) => MastodonStatusContextState;
    toggleContext: (statusId: string) => void | Promise<void>;
}>();

const { t } = useI18n();
</script>

<template>
    <section v-if="statuses.length > 0" class="space-y-3">
        <div
            class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
        >
            {{ t('mastodon.sections.statuses') }}
        </div>
        <article
            v-for="status in statuses"
            :key="status.id"
            class="rounded-lg border border-border/80 bg-background/70 p-3"
        >
            <div class="mb-2 flex items-center gap-3">
                <img
                    v-if="status.account.avatar"
                    :src="status.account.avatar"
                    :alt="status.account.displayName || status.account.acct"
                    class="h-10 w-10 rounded-full object-cover"
                    loading="lazy"
                />
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold">
                        {{
                            status.account.displayName ||
                            status.account.username
                        }}
                    </div>
                    <div class="truncate text-xs text-muted-foreground">
                        @{{ status.account.acct }} |
                        {{
                            status.instanceDomain ||
                            status.account.instanceDomain
                        }}
                        | {{ formatDate(status.createdAt) }}
                    </div>
                </div>
            </div>

            <p
                v-if="status.spoilerText"
                class="mb-2 rounded-md border border-amber-500/30 bg-amber-500/10 px-2 py-1 text-xs text-amber-700"
            >
                {{ status.spoilerText }}
            </p>

            <p class="text-sm leading-relaxed text-foreground">
                {{ status.content || t('mastodon.search.noText') }}
            </p>

            <div
                class="mt-3 flex flex-wrap gap-3 text-xs text-muted-foreground"
            >
                <span
                    >{{ t('mastodon.metrics.postType') }}:
                    {{ t(`mastodon.postTypes.${status.postType}`) }}</span
                >
                <span
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
                <span
                    >{{ t('mastodon.metrics.media') }}:
                    {{ status.mediaAttachmentsCount }}</span
                >
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <a
                    v-if="status.url"
                    :href="status.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                >
                    <ExternalLink class="mr-1 inline h-3 w-3" />
                    {{ t('mastodon.common.open') }}
                </a>

                <span
                    v-for="tag in status.tags"
                    :key="`${status.id}-${tag}`"
                    class="rounded-full border border-input px-2 py-1 text-xs text-muted-foreground"
                >
                    #{{ tag }}
                </span>

                <span
                    v-for="domain in status.domains"
                    :key="`${status.id}-domain-${domain}`"
                    class="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2 py-1 text-xs text-cyan-700"
                >
                    {{ domain }}
                </span>

                <button
                    v-if="status.repliesCount > 0"
                    type="button"
                    class="cursor-pointer rounded-full border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent"
                    @click="toggleContext(status.id)"
                >
                    {{
                        ensureContextState(status.id).open
                            ? t('mastodon.comments.hide')
                            : `${t('mastodon.comments.show')} (${status.repliesCount})`
                    }}
                </button>
            </div>

            <div
                v-if="status.mentions.length > 0 || status.links.length > 0"
                class="mt-3 space-y-2 text-xs"
            >
                <div
                    v-if="status.mentions.length > 0"
                    class="flex flex-wrap gap-2"
                >
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

            <div
                v-if="ensureContextState(status.id).open"
                class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3"
            >
                <p
                    v-if="ensureContextState(status.id).loading"
                    class="text-xs text-muted-foreground"
                >
                    {{ t('mastodon.comments.loading') }}
                </p>

                <p
                    v-else-if="ensureContextState(status.id).error"
                    class="text-xs text-destructive"
                >
                    {{ ensureContextState(status.id).error }}
                </p>

                <div v-else class="space-y-3">
                    <div
                        v-if="
                            ensureContextState(status.id).ancestors.length > 0
                        "
                        class="space-y-2"
                    >
                        <div
                            class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{ t('mastodon.comments.ancestors') }}
                        </div>
                        <article
                            v-for="item in ensureContextState(status.id)
                                .ancestors"
                            :key="`${status.id}-ancestor-${item.id}`"
                            class="rounded-md border border-border/70 bg-background/70 p-2"
                        >
                            <div class="mb-1 text-[11px] text-muted-foreground">
                                @{{ item.account.acct }} |
                                {{ formatDate(item.createdAt) }}
                            </div>
                            <p class="text-xs leading-relaxed text-foreground">
                                {{
                                    item.content || t('mastodon.search.noText')
                                }}
                            </p>
                        </article>
                    </div>

                    <div class="space-y-2">
                        <div
                            class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{ t('mastodon.comments.replies') }}
                        </div>

                        <p
                            v-if="
                                ensureContextState(status.id).descendants
                                    .length === 0
                            "
                            class="text-xs text-muted-foreground"
                        >
                            {{ t('mastodon.comments.empty') }}
                        </p>

                        <MastodonReplyThread
                            v-else
                            :items="
                                ensureContextState(status.id).descendantsTree
                            "
                            :no-text-label="t('mastodon.search.noText')"
                        />
                    </div>
                </div>
            </div>
        </article>
    </section>
</template>
