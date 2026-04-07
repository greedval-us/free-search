<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Telegram',
                href: '/telegram',
            },
        ],
    },
});

type SearchItem = {
    id: number;
    date: number;
    message: string;
    fromId: number;
    authorId: number | null;
    peerId: number;
    views: number | null;
    forwards: number | null;
    postAuthor: string | null;
    authorSignature: string | null;
    repliesCount: number | null;
    telegramUrl: string | null;
    media: {
        hasMedia: boolean;
        type: string;
        label: string;
        rawType: string | null;
    };
    reactions: Array<{
        emoji: string;
        count: number;
    }>;
    gifts: {
        hasGift: boolean;
        types: string[];
    };
};

type SearchResponse = {
    ok: boolean;
    message?: string;
    items: SearchItem[];
    pagination: {
        limit: number;
        offsetId: number;
        nextOffsetId: number | null;
        hasMore: boolean;
        total: number;
    };
};

const form = reactive({
    chatUsername: 'durov',
    q: '',
    fromUsername: '',
    dateFrom: '',
    dateTo: '',
    limit: 20,
});

const loading = ref(false);
const loadingMore = ref(false);
const error = ref<string | null>(null);
const items = ref<SearchItem[]>([]);
const total = ref(0);
const nextOffsetId = ref<number | null>(null);
const hasMore = ref(false);
const showAdvanced = ref(false);

const canSearch = computed(() => form.chatUsername.trim().length > 0);
const loadedCount = computed(() => items.value.length);
const LIMIT_MIN = 1;
const LIMIT_MAX = 50;

const clampLimit = () => {
    const value = Number(form.limit);

    if (!Number.isFinite(value)) {
        form.limit = 20;

        return;
    }

    form.limit = Math.min(LIMIT_MAX, Math.max(LIMIT_MIN, Math.trunc(value)));
};

const buildQuery = (params: Record<string, string | number>) => {
    const query = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (String(value).length > 0) {
            query.set(key, String(value));
        }
    });

    return query.toString();
};

const formatDate = (unix: number) => {
    if (!unix) {
        return '-';
    }

    return new Date(unix * 1000).toLocaleString();
};

const mediaLabel = (type: string) => {
    const map: Record<string, string> = {
        photo: 'Фото',
        video: 'Видео',
        document: 'Документ',
        audio: 'Аудио',
        geo: 'Гео',
        poll: 'Опрос',
        contact: 'Контакт',
        link_preview: 'Ссылка',
        other: 'Медиа',
        none: 'Нет',
    };

    return map[type] ?? 'Медиа';
};

const searchMessages = async (append = false) => {
    if (!canSearch.value) {
        error.value = 'Сначала укажите username канала.';

        return;
    }

    error.value = null;

    if (append) {
        loadingMore.value = true;
    } else {
        loading.value = true;
        items.value = [];
        nextOffsetId.value = null;
        hasMore.value = false;
        total.value = 0;
    }

    try {
        const query = buildQuery({
            chatUsername: form.chatUsername.trim(),
            q: form.q.trim(),
            fromUsername: form.fromUsername.trim(),
            dateFrom: form.dateFrom,
            dateTo: form.dateTo,
            limit: Math.min(LIMIT_MAX, Math.max(LIMIT_MIN, form.limit)),
            offsetId: append ? (nextOffsetId.value ?? 0) : 0,
        });

        const response = await fetch(`/telegram/search/messages?${query}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
            },
        });

        const payload: SearchResponse = await response.json();

        if (!response.ok || !payload.ok) {
            error.value = payload.message ?? 'Ошибка запроса поиска.';

            return;
        }

        const incoming = payload.items ?? [];

        if (append) {
            const known = new Set(items.value.map((item) => item.id));

            for (const item of incoming) {
                if (!known.has(item.id)) {
                    items.value.push(item);
                }
            }
        } else {
            items.value = incoming;
        }

        total.value = payload.pagination.total ?? items.value.length;
        nextOffsetId.value = payload.pagination.nextOffsetId;
        hasMore.value = payload.pagination.hasMore;
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Неизвестная ошибка поиска.';
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};
</script>

<template>
    <Head title="Telegram" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div class="flex flex-wrap items-end gap-3">
                <div class="grid min-w-0 flex-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <label class="block min-w-0">
                        <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">Username канала</span>
                        <input
                            v-model="form.chatUsername"
                            type="text"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            placeholder="durov"
                        />
                    </label>

                    <label class="block min-w-0">
                        <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">Ключевое слово</span>
                        <input
                            v-model="form.q"
                            type="text"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            placeholder="безопасность"
                        />
                    </label>

                    <label class="block min-w-0">
                        <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">ID автора</span>
                        <input
                            v-model="form.fromUsername"
                            type="text"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            placeholder="@username или id"
                        />
                    </label>
                </div>

                <div class="flex w-full flex-wrap gap-2 lg:w-auto">
                    <button
                        type="button"
                        class="h-10 rounded-md border border-input px-4 text-sm font-medium text-foreground hover:bg-accent"
                        @click="showAdvanced = !showAdvanced"
                    >
                        {{ showAdvanced ? 'Скрыть фильтры' : 'Доп. фильтры' }}
                    </button>

                    <button
                        :disabled="loading || !canSearch"
                        class="h-10 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:opacity-60"
                        @click="searchMessages(false)"
                    >
                        {{ loading ? 'Поиск...' : 'Найти' }}
                    </button>
                </div>
            </div>

            <div
                v-if="showAdvanced"
                class="mt-3 grid gap-3 border-t border-border/60 pt-3 md:grid-cols-3"
            >
                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">Лимит</span>
                    <input
                        v-model.number="form.limit"
                        type="number"
                        min="1"
                        max="50"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        @input="clampLimit"
                        @blur="clampLimit"
                    />
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">Дата с</span>
                    <input
                        v-model="form.dateFrom"
                        type="date"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">Дата по</span>
                    <input
                        v-model="form.dateTo"
                        type="date"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>
            </div>

            <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
        </section>

        <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold">Сообщения</h2>
                <p class="text-xs text-muted-foreground">Показано: {{ loadedCount }} из {{ total }}</p>
            </div>

            <div class="telegram-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1">
                <div v-if="!loading && items.length === 0" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                    Выполните поиск, чтобы увидеть сообщения.
                </div>

                <div v-else class="space-y-3">
                    <article
                        v-for="item in items"
                        :key="item.id"
                        class="rounded-lg border border-border/80 bg-background/70 p-3"
                    >
                        <div class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                            <span>Автор ID: {{ item.authorId ?? '-' }}</span>
                            <span>Date: {{ formatDate(item.date) }}</span>
                            <span>Просмотры: {{ item.views ?? '-' }}</span>
                            <span>Репосты: {{ item.forwards ?? '-' }}</span>
                            <span>Ответы: {{ item.repliesCount ?? '-' }}</span>
                            <span>Медиа: {{ mediaLabel(item.media.type) }}</span>
                            <span v-if="item.gifts.hasGift" class="text-amber-500">Подарок: да</span>
                        </div>

                        <p class="text-sm leading-relaxed text-foreground">
                            {{ item.message || '[Без текста]' }}
                        </p>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <a
                                v-if="item.telegramUrl"
                                :href="item.telegramUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                            >
                                Открыть в Telegram
                            </a>

                            <span
                                v-if="item.media.hasMedia"
                                class="rounded-full border border-input px-2 py-1 text-xs text-muted-foreground"
                            >
                                {{ item.media.label || mediaLabel(item.media.type) }}
                            </span>

                            <span
                                v-for="reaction in item.reactions"
                                :key="`${item.id}-${reaction.emoji}`"
                                class="rounded-full border border-input px-2 py-1 text-xs"
                            >
                                {{ reaction.emoji }} {{ reaction.count }}
                            </span>

                            <span
                                v-for="giftType in item.gifts.types"
                                :key="`${item.id}-${giftType}`"
                                class="rounded-full border border-amber-400/40 bg-amber-400/10 px-2 py-1 text-xs text-amber-600 dark:text-amber-300"
                            >
                                {{ giftType }}
                            </span>
                        </div>
                    </article>
                </div>
            </div>

            <div class="mt-4 flex justify-center" v-if="hasMore">
                <button
                    :disabled="loadingMore"
                    class="rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent disabled:opacity-60"
                    @click="searchMessages(true)"
                >
                    {{ loadingMore ? 'Загрузка...' : 'Показать еще' }}
                </button>
            </div>
        </section>
    </div>
</template>
