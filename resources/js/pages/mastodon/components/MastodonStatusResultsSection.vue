<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type { MastodonStatus, MastodonStatusContextState } from '../types';
import MastodonReplyThread from './MastodonReplyThread.vue';
import MastodonStatusCard from './MastodonStatusCard.vue';

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
        <div class="intel-subsection-title">
            {{ t('mastodon.sections.statuses') }}
        </div>
        <MastodonStatusCard
            v-for="status in statuses"
            :key="status.id"
            :status="status"
            :format-date="formatDate"
            :show-language="true"
            :show-media-count="true"
        >
            <template #actions>
                <button
                    v-if="status.repliesCount > 0"
                    type="button"
                    class="intel-action"
                    @click="toggleContext(status.id)"
                >
                    {{
                        ensureContextState(status.id).open
                            ? t('mastodon.comments.hide')
                            : `${t('mastodon.comments.show')} (${status.repliesCount})`
                    }}
                </button>
            </template>

            <template #details>
                <div
                    v-if="ensureContextState(status.id).open"
                    class="intel-subsection"
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
                                ensureContextState(status.id).ancestors.length >
                                0
                            "
                            class="space-y-2"
                        >
                            <div class="intel-subsection-title">
                                {{ t('mastodon.comments.ancestors') }}
                            </div>
                            <article
                                v-for="item in ensureContextState(status.id)
                                    .ancestors"
                                :key="`${status.id}-ancestor-${item.id}`"
                                class="rounded-md border border-border/70 bg-background/70 p-2"
                            >
                                <div
                                    class="mb-1 text-[11px] text-muted-foreground"
                                >
                                    @{{ item.account.acct }} |
                                    {{ formatDate(item.createdAt) }}
                                </div>
                                <p
                                    class="text-xs leading-relaxed text-foreground"
                                >
                                    {{
                                        item.content ||
                                        t('mastodon.search.noText')
                                    }}
                                </p>
                            </article>
                        </div>

                        <div class="space-y-2">
                            <div class="intel-subsection-title">
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
                                    ensureContextState(status.id)
                                        .descendantsTree
                                "
                                :no-text-label="t('mastodon.search.noText')"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </MastodonStatusCard>
    </section>
</template>
