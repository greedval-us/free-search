<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { dashboard } from '@/routes';

type Param = {
    name: string;
    description: string;
    why: string;
    example: string;
};
type Stream = { name: string; items: string[] };
type ModuleDoc = {
    route: string;
    title: string;
    purpose: string;
    scenarios: string[];
    params: Param[];
    outputs: string[];
    streams: Stream[];
};

const { locale } = useI18n();
const isRu = computed(() => locale.value === 'ru');

const ruModules: ModuleDoc[] = [
    {
        route: '/telegram',
        title: 'Telegram',
        purpose: 'Оперативный сбор и аналитика данных Telegram-каналов.',
        scenarios: [
            'Мониторинг инфополя',
            'Поиск аномалий активности',
            'Сбор хронологии доказательств',
        ],
        params: [
            {
                name: 'chatUsername',
                description: 'Имя канала/чата.',
                why: 'Определяет источник данных.',
                example: 'durov',
            },
            {
                name: 'keyword',
                description: 'Ключевое слово или фраза.',
                why: 'Сужает выборку и снижает шум.',
                example: 'санкции',
            },
            {
                name: 'fromUsername/authorId',
                description: 'Фильтр по автору.',
                why: 'Нужен для анализа конкретного автора.',
                example: '123456789',
            },
            {
                name: 'dateFrom/dateTo',
                description: 'Период анализа.',
                why: 'Ограничивает данные нужным интервалом.',
                example: '2026-05-01 .. 2026-05-07',
            },
            {
                name: 'limit',
                description: 'Лимит сообщений.',
                why: 'Баланс между скоростью и полнотой.',
                example: '20',
            },
        ],
        outputs: [
            'Результаты поиска',
            'Аналитические метрики',
            'Экспортируемые отчеты',
        ],
        streams: [
            {
                name: 'Поиск',
                items: [
                    'Сообщения: текст, дата, автор, просмотры, репосты, ответы',
                    'Медиа-данные',
                    'Реакции и подарки',
                    'Комментарии с пагинацией',
                ],
            },
            {
                name: 'Аналитика',
                items: [
                    'Ключевые показатели за период',
                    'Профиль канала/группы',
                    'Anti-fraud: риск, триггеры, причины',
                    'Графики, топ-посты, лидеры мнений',
                    'Открытие/скачивание отчета',
                ],
            },
            {
                name: 'Парсер',
                items: [
                    'Статусы запуска и выполнения',
                    'Прогресс и счетчики обработки',
                    'Сбор по периодам и keyword-режиму',
                    'Выгрузки в Excel и JSON',
                ],
            },
        ],
    },
    {
        route: '/youtube',
        title: 'YouTube',
        purpose:
            'Поиск, аналитика и сбор комментариев по YouTube-видео и каналам.',
        scenarios: [
            'Мониторинг видеоповестки',
            'Анализ каналов и вовлеченности',
            'Сбор комментариев для дальнейшей проверки',
        ],
        params: [
            {
                name: 'q',
                description: 'Поисковый запрос.',
                why: 'Определяет тему, видео, канал или плейлист для поиска.',
                example: 'osint tutorial',
            },
            {
                name: 'type',
                description: 'Тип результата: video, channel или playlist.',
                why: 'Позволяет сузить выдачу под нужную сущность.',
                example: 'video',
            },
            {
                name: 'channelId',
                description: 'ID канала YouTube.',
                why: 'Ограничивает поиск или аналитику конкретным каналом.',
                example: 'UC_x5XG1OV2P6uZZ5FSM9Ttw',
            },
            {
                name: 'videoId',
                description: 'ID или ссылка на видео.',
                why: 'Используется для аналитики видео и сбора комментариев.',
                example: 'dQw4w9WgXcQ',
            },
            {
                name: 'publishedAfter/publishedBefore',
                description: 'Период публикации для поиска.',
                why: 'Фильтрует выдачу по нужному временному окну.',
                example: '2026-05-01 .. 2026-05-07',
            },
            {
                name: 'dateFrom/dateTo или periodDays',
                description: 'Период аналитики канала.',
                why: 'Задает окно расчета метрик и таймлайна.',
                example: '7',
            },
            {
                name: 'limit/pageToken',
                description: 'Лимит и токен пагинации.',
                why: 'Управляют объемом выдачи и переходом к следующей странице.',
                example: '10',
            },
        ],
        outputs: [
            'Видео, каналы и плейлисты из поиска',
            'Сводка аналитики по видео или каналу',
            'Комментарии и ответы',
            'HTML-отчет и выгрузки Excel/JSON',
        ],
        streams: [
            {
                name: 'Поиск',
                items: [
                    'Результаты YouTube Data API: заголовок, описание, канал, дата публикации, превью',
                    'Метрики видео: просмотры, лайки, комментарии, engagement rate',
                    'Фильтры по региону, языку, safe search, длительности, качеству и субтитрам',
                    'Пагинация через nextPageToken',
                ],
            },
            {
                name: 'Аналитика',
                items: [
                    'Режимы video и channel',
                    'Суммарные метрики: views, likes, comments, средние и медианные значения',
                    'Распределения по времени, длительности, качеству и наличию субтитров',
                    'Лидеры по просмотрам, лайкам, комментариям и вовлеченности',
                    'HTML-отчет с возможностью скачивания',
                ],
            },
            {
                name: 'Парсер',
                items: [
                    'Получение комментариев и ответов по videoId',
                    'Сортировка по relevance или time и поиск внутри комментариев',
                    'Фоновый запуск, статус, остановка и прогресс выполнения',
                    'Экспорт результатов в Excel и JSON',
                ],
            },
        ],
    },
    {
        route: '/username',
        title: 'Username',
        purpose: 'Поиск цифровых следов по username.',
        scenarios: [
            'Проверка псевдонима',
            'Проверка имперсонации',
            'Кросс-платформенный pivot',
        ],
        params: [
            {
                name: 'username',
                description: 'Искомый username.',
                why: 'Основная сущность поиска.',
                example: 'greedval',
            },
            {
                name: 'autorun',
                description: 'Автозапуск запроса.',
                why: 'Ускоряет повторные проверки.',
                example: '1',
            },
        ],
        outputs: [
            'Статусы найдено/не найдено',
            'Confidence-сигналы',
            'Граф сущностей',
        ],
        streams: [
            {
                name: 'Поиск',
                items: [
                    'Статусы по платформам',
                    'Ссылки на найденные профили',
                    'Категории и регионы платформ',
                ],
            },
            {
                name: 'Аналитика',
                items: [
                    'Распределение confidence',
                    'Похожие варианты username',
                    'Entity graph (nodes/edges)',
                ],
            },
        ],
    },
    {
        route: '/site-intel',
        title: 'Site Intel',
        purpose: 'Техническая разведка сайта и домена.',
        scenarios: [
            'Проверка доступности и безопасности',
            'SEO-аудит',
            'Аналитика домена',
        ],
        params: [
            {
                name: 'target/domain',
                description: 'URL или домен.',
                why: 'Цель анализа.',
                example: 'example.com',
            },
        ],
        outputs: ['DNS/SSL/headers', 'WHOIS/DNS сводки', 'SEO findings'],
        streams: [
            {
                name: 'Режимы',
                items: [
                    'Здоровье сайта',
                    'Базовый домен',
                    'Аналитика',
                    'SEO-аудит',
                ],
            },
        ],
    },
    {
        route: '/fio',
        title: 'FIO',
        purpose: 'Поиск по ФИО и связанным упоминаниям.',
        scenarios: ['Первичная проверка персоны', 'Уточнение по контексту'],
        params: [
            {
                name: 'full_name',
                description: 'ФИО.',
                why: 'Основной ключ поиска.',
                example: 'Иванов Иван',
            },
        ],
        outputs: ['Упоминания и источники', 'Контекстные сигналы'],
        streams: [
            {
                name: 'Поиск',
                items: ['Список упоминаний', 'Ссылки на источники'],
            },
        ],
    },
    {
        route: '/company-intel',
        title: 'Company Intel',
        purpose: 'Сбор открытых данных о компании.',
        scenarios: ['Due diligence', 'Enrichment профиля'],
        params: [
            {
                name: 'query',
                description: 'Компания/бренд/домен.',
                why: 'Объект проверки.',
                example: 'openai.com',
            },
        ],
        outputs: ['Профиль компании', 'Публичные связи'],
        streams: [
            {
                name: 'Поиск',
                items: [
                    'Company profile blocks',
                    'Reference links',
                    'Related entities',
                ],
            },
        ],
    },
    {
        route: '/document-intel',
        title: 'Document Intel',
        purpose: 'Поиск документов и извлечение сигналов.',
        scenarios: ['Сбор доказательств', 'Проверка источников'],
        params: [
            {
                name: 'query',
                description: 'Домен/ФИО/ключ.',
                why: 'База поиска документов.',
                example: 'hh.ru',
            },
        ],
        outputs: ['Ссылки на документы', 'Метаданные и сводки'],
        streams: [
            {
                name: 'Поиск',
                items: ['Document links', 'Metadata', 'Extracted insights'],
            },
        ],
    },
    {
        route: '/email-intel',
        title: 'Email Intel',
        purpose: 'Проверка цифровых следов email.',
        scenarios: ['Валидация контакта', 'Оценка риска'],
        params: [
            {
                name: 'email',
                description: 'Email.',
                why: 'Основная сущность.',
                example: 'name@example.com',
            },
        ],
        outputs: ['Сигналы по адресу/домену'],
        streams: [
            {
                name: 'Режимы',
                items: ['Поиск', 'Аналитика', 'Пакетный режим', 'Режим домена'],
            },
        ],
    },
    {
        route: '/domain-infra-intel',
        title: 'Domain & Infra Intel',
        purpose: 'Картирование инфраструктуры домена.',
        scenarios: ['Infra clustering', 'Поиск соседних активов'],
        params: [
            {
                name: 'domain',
                description: 'Домен/URL.',
                why: 'Стартовая точка infra pivot.',
                example: 'habr.com',
            },
        ],
        outputs: ['IP/ASN/RDAP/WHOIS', 'Neighbor domains', 'CT sightings'],
        streams: [
            {
                name: 'Поиск',
                items: ['Resolved IPs', 'ASN owner', 'RDAP/WHOIS payloads'],
            },
        ],
    },
    {
        route: '/news-media-intel',
        title: 'News & Media Intel',
        purpose: 'Мониторинг медиа-упоминаний.',
        scenarios: ['Репутационный мониторинг', 'Отслеживание инфоповодов'],
        params: [
            {
                name: 'query',
                description: 'Тема/персона/бренд.',
                why: 'Объект мониторинга.',
                example: 'война',
            },
        ],
        outputs: ['Лента упоминаний', 'Тональность', 'Таймлайн'],
        streams: [
            {
                name: 'Поиск',
                items: [
                    'Mentions feed',
                    'Topic summary',
                    'Sentiment',
                    'Timeline',
                ],
            },
        ],
    },
    {
        route: '/shifr',
        title: 'Shifr',
        purpose: 'Технический toolbox для трансформаций.',
        scenarios: ['Hash/Decode', 'IOC extraction', 'JWT и classic ciphers'],
        params: [
            {
                name: 'tab/input',
                description: 'Режим и вход.',
                why: 'Определяют операцию.',
                example: 'hash + text',
            },
        ],
        outputs: ['Структурированный результат операции'],
        streams: [
            {
                name: 'Режимы',
                items: [
                    'Хэш/HMAC',
                    'Преобразование',
                    'Извлечение IOC',
                    'Классические шифры',
                    'JWT',
                ],
            },
        ],
    },
];

const enModules: ModuleDoc[] = [
    {
        route: '/telegram',
        title: 'Telegram',
        purpose: 'Operational collection and analytics for Telegram channels.',
        scenarios: [
            'Narrative monitoring',
            'Activity anomaly detection',
            'Evidence timeline building',
        ],
        params: [
            {
                name: 'chatUsername',
                description: 'Channel/chat username.',
                why: 'Defines the data source.',
                example: 'durov',
            },
            {
                name: 'keyword',
                description: 'Keyword or phrase.',
                why: 'Narrows the dataset and reduces noise.',
                example: 'sanctions',
            },
            {
                name: 'fromUsername/authorId',
                description: 'Author filter.',
                why: 'Targets a specific author.',
                example: '123456789',
            },
            {
                name: 'dateFrom/dateTo',
                description: 'Analysis period.',
                why: 'Limits data to a required window.',
                example: '2026-05-01 .. 2026-05-07',
            },
            {
                name: 'limit',
                description: 'Message limit.',
                why: 'Balances speed and coverage.',
                example: '20',
            },
        ],
        outputs: ['Search results', 'Analytics metrics', 'Exportable reports'],
        streams: [
            {
                name: 'Search',
                items: [
                    'Messages: text/date/author/views/forwards/replies',
                    'Media data',
                    'Reactions and gifts',
                    'Thread comments with pagination',
                ],
            },
            {
                name: 'Analytics',
                items: [
                    'Period KPI summary',
                    'Channel/group profile',
                    'Anti-fraud risk/triggers/reasons',
                    'Charts, top posts, opinion leaders',
                    'Open/download report',
                ],
            },
            {
                name: 'Parser',
                items: [
                    'Run and processing statuses',
                    'Progress and processed counters',
                    'Period-based and keyword-based collection',
                    'Excel and JSON exports',
                ],
            },
        ],
    },
    {
        route: '/youtube',
        title: 'YouTube',
        purpose:
            'Search, analytics, and comment collection for YouTube videos and channels.',
        scenarios: [
            'Video narrative monitoring',
            'Channel and engagement analysis',
            'Comment collection for follow-up review',
        ],
        params: [
            {
                name: 'q',
                description: 'Search query.',
                why: 'Defines the topic, video, channel, or playlist lookup.',
                example: 'osint tutorial',
            },
            {
                name: 'type',
                description: 'Result type: video, channel, or playlist.',
                why: 'Narrows results to the required YouTube entity.',
                example: 'video',
            },
            {
                name: 'channelId',
                description: 'YouTube channel ID.',
                why: 'Limits search or analytics to a specific channel.',
                example: 'UC_x5XG1OV2P6uZZ5FSM9Ttw',
            },
            {
                name: 'videoId',
                description: 'Video ID or URL.',
                why: 'Used for video analytics and comment collection.',
                example: 'dQw4w9WgXcQ',
            },
            {
                name: 'publishedAfter/publishedBefore',
                description: 'Search publication window.',
                why: 'Filters search results to the required time range.',
                example: '2026-05-01 .. 2026-05-07',
            },
            {
                name: 'dateFrom/dateTo or periodDays',
                description: 'Channel analytics period.',
                why: 'Controls the KPI and timeline calculation window.',
                example: '7',
            },
            {
                name: 'limit/pageToken',
                description: 'Result limit and pagination token.',
                why: 'Controls response size and next-page navigation.',
                example: '10',
            },
        ],
        outputs: [
            'Search videos, channels, and playlists',
            'Video or channel analytics summary',
            'Comments and replies',
            'HTML report and Excel/JSON exports',
        ],
        streams: [
            {
                name: 'Search',
                items: [
                    'YouTube Data API results: title, description, channel, publication date, thumbnail',
                    'Video metrics: views, likes, comments, engagement rate',
                    'Region, language, safe search, duration, definition, and caption filters',
                    'Pagination via nextPageToken',
                ],
            },
            {
                name: 'Analytics',
                items: [
                    'Video and channel modes',
                    'Totals: views, likes, comments, averages, medians',
                    'Distributions by time, duration, definition, and captions',
                    'Leaders by views, likes, comments, and engagement',
                    'Downloadable HTML report',
                ],
            },
            {
                name: 'Parser',
                items: [
                    'Comments and replies by videoId',
                    'Relevance/time sorting and search within comments',
                    'Background run start, status, stop, and progress tracking',
                    'Excel and JSON exports',
                ],
            },
        ],
    },
    {
        route: '/username',
        title: 'Username',
        purpose: 'Digital trace lookup by username.',
        scenarios: [
            'Alias checks',
            'Impersonation checks',
            'Cross-platform pivots',
        ],
        params: [
            {
                name: 'username',
                description: 'Target username.',
                why: 'Primary lookup entity.',
                example: 'greedval',
            },
            {
                name: 'autorun',
                description: 'Auto-run query.',
                why: 'Speeds up repeated checks.',
                example: '1',
            },
        ],
        outputs: [
            'Found/not found statuses',
            'Confidence signals',
            'Entity graph',
        ],
        streams: [
            {
                name: 'Search',
                items: [
                    'Platform statuses',
                    'Matched profile links',
                    'Platform categories and regions',
                ],
            },
            {
                name: 'Analytics',
                items: [
                    'Confidence distribution',
                    'Username similarity variants',
                    'Entity graph (nodes/edges)',
                ],
            },
        ],
    },
    {
        route: '/site-intel',
        title: 'Site Intel',
        purpose: 'Technical reconnaissance for websites and domains.',
        scenarios: [
            'Availability/security checks',
            'SEO audit',
            'Domain analytics',
        ],
        params: [
            {
                name: 'target/domain',
                description: 'URL or domain.',
                why: 'Analysis target.',
                example: 'example.com',
            },
        ],
        outputs: ['DNS/SSL/headers', 'WHOIS/DNS summaries', 'SEO findings'],
        streams: [
            {
                name: 'Modes',
                items: ['Site Health', 'Domain Lite', 'Analytics', 'SEO Audit'],
            },
        ],
    },
    {
        route: '/fio',
        title: 'FIO',
        purpose: 'Lookup by full name and related mentions.',
        scenarios: ['Initial person check', 'Context narrowing'],
        params: [
            {
                name: 'full_name',
                description: 'Full name.',
                why: 'Core search key.',
                example: 'John Smith',
            },
        ],
        outputs: ['Mentions and sources', 'Context signals'],
        streams: [{ name: 'Lookup', items: ['Mentions list', 'Source links'] }],
    },
    {
        route: '/company-intel',
        title: 'Company Intel',
        purpose: 'Open-source company intelligence.',
        scenarios: ['Due diligence', 'Entity enrichment'],
        params: [
            {
                name: 'query',
                description: 'Company/brand/domain.',
                why: 'Target entity.',
                example: 'openai.com',
            },
        ],
        outputs: ['Company profile', 'Public relations'],
        streams: [
            {
                name: 'Lookup',
                items: [
                    'Company profile blocks',
                    'Reference links',
                    'Related entities',
                ],
            },
        ],
    },
    {
        route: '/document-intel',
        title: 'Document Intel',
        purpose: 'Document discovery and signal extraction.',
        scenarios: ['Evidence collection', 'Source validation'],
        params: [
            {
                name: 'query',
                description: 'Domain/person/keyword.',
                why: 'Document search basis.',
                example: 'hh.ru',
            },
        ],
        outputs: ['Document links', 'Metadata and summaries'],
        streams: [
            {
                name: 'Lookup',
                items: ['Document links', 'Metadata', 'Extracted insights'],
            },
        ],
    },
    {
        route: '/email-intel',
        title: 'Email Intel',
        purpose: 'Digital trace checks for email identities.',
        scenarios: ['Contact validation', 'Risk review'],
        params: [
            {
                name: 'email',
                description: 'Email address.',
                why: 'Primary entity.',
                example: 'name@example.com',
            },
        ],
        outputs: ['Address/domain signals'],
        streams: [
            { name: 'Modes', items: ['Search', 'Analytics', 'Bulk', 'Domain'] },
        ],
    },
    {
        route: '/domain-infra-intel',
        title: 'Domain & Infra Intel',
        purpose: 'Infrastructure mapping for domains.',
        scenarios: ['Infra clustering', 'Related asset discovery'],
        params: [
            {
                name: 'domain',
                description: 'Domain/URL.',
                why: 'Infra pivot entrypoint.',
                example: 'habr.com',
            },
        ],
        outputs: ['IP/ASN/RDAP/WHOIS', 'Neighbor domains', 'CT sightings'],
        streams: [
            {
                name: 'Lookup',
                items: ['Resolved IPs', 'ASN owner', 'RDAP/WHOIS payloads'],
            },
        ],
    },
    {
        route: '/news-media-intel',
        title: 'News & Media Intel',
        purpose: 'Public media mention monitoring.',
        scenarios: ['Reputation watch', 'Event tracking'],
        params: [
            {
                name: 'query',
                description: 'Topic/person/brand.',
                why: 'Monitoring target.',
                example: 'war',
            },
        ],
        outputs: ['Mentions feed', 'Sentiment', 'Timeline'],
        streams: [
            {
                name: 'Lookup',
                items: [
                    'Mentions feed',
                    'Topic summary',
                    'Sentiment',
                    'Timeline',
                ],
            },
        ],
    },
    {
        route: '/shifr',
        title: 'Shifr',
        purpose: 'Technical toolbox for transformations.',
        scenarios: ['Hash/Decode', 'IOC extraction', 'JWT and classic ciphers'],
        params: [
            {
                name: 'tab/input',
                description: 'Mode and input.',
                why: 'Define operation behavior.',
                example: 'hash + text',
            },
        ],
        outputs: ['Structured operation result'],
        streams: [
            {
                name: 'Modes',
                items: ['Hash/HMAC', 'Transform', 'IOC', 'Classic', 'JWT'],
            },
        ],
    },
];

const modules = computed(() => (isRu.value ? ruModules : enModules));

const ui = computed(() => ({
    title: isRu.value ? 'Вики модулей' : 'Modules Wiki',
    subtitle: isRu.value
        ? 'Подробное описание параметров и формируемых данных по каждому модулю.'
        : 'Detailed parameters and produced datasets for each module.',
    purpose: isRu.value ? 'Зачем нужен модуль' : 'Purpose',
    scenarios: isRu.value ? 'Когда использовать' : 'When to use',
    params: isRu.value ? 'Параметры' : 'Parameters',
    outputs: isRu.value ? 'Результат' : 'Output',
    streams: isRu.value ? 'Какие данные формируются' : 'Produced data',
    open: isRu.value ? 'Открыть модуль' : 'Open module',
    example: isRu.value ? 'Пример' : 'Example',
}));

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Wiki / Modules', href: '/wiki/modules' },
        ],
    },
});
</script>

<template>
    <Head :title="ui.title" />

    <div class="mx-auto w-full max-w-7xl space-y-4 p-4 sm:p-6">
        <section class="intel-panel-strong">
            <h1 class="text-xl font-semibold sm:text-2xl">{{ ui.title }}</h1>
            <p class="mt-2 text-sm text-muted-foreground">{{ ui.subtitle }}</p>
        </section>

        <section class="intel-panel p-3 sm:p-4">
            <div
                class="intel-scroll max-h-[72vh] overflow-y-auto pr-1 [scrollbar-gutter:stable] sm:pr-2"
            >
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <article
                        v-for="m in modules"
                        :key="m.route"
                        class="intel-surface rounded-xl p-4"
                    >
                        <div
                            class="mb-3 flex items-start justify-between gap-3"
                        >
                            <h2 class="text-lg font-semibold">{{ m.title }}</h2>
                            <a
                                :href="m.route"
                                class="inline-flex rounded-md border border-primary/30 bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                            >
                                {{ ui.open }}
                            </a>
                        </div>

                        <div class="space-y-4 text-sm">
                            <div>
                                <p class="intel-title font-medium">
                                    {{ ui.purpose }}
                                </p>
                                <p class="mt-1 text-muted-foreground">
                                    {{ m.purpose }}
                                </p>
                            </div>

                            <div>
                                <p class="intel-title font-medium">
                                    {{ ui.scenarios }}
                                </p>
                                <ul
                                    class="mt-1 list-disc space-y-1 pl-5 text-muted-foreground"
                                >
                                    <li v-for="s in m.scenarios" :key="s">
                                        {{ s }}
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <p class="intel-title font-medium">
                                    {{ ui.params }}
                                </p>
                                <div class="mt-2 space-y-2">
                                    <div
                                        v-for="p in m.params"
                                        :key="`${m.route}-${p.name}`"
                                        class="intel-section rounded-lg p-3"
                                    >
                                        <p class="text-sm font-semibold">
                                            {{ p.name }}
                                        </p>
                                        <p class="mt-1 text-muted-foreground">
                                            {{ p.description }}
                                        </p>
                                        <p class="mt-1 text-muted-foreground">
                                            {{ p.why }}
                                        </p>
                                        <p class="mt-1 text-xs text-primary">
                                            {{ ui.example }}:
                                            <span class="font-mono">{{
                                                p.example
                                            }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p class="intel-title font-medium">
                                    {{ ui.outputs }}
                                </p>
                                <ul
                                    class="mt-1 list-disc space-y-1 pl-5 text-muted-foreground"
                                >
                                    <li v-for="o in m.outputs" :key="o">
                                        {{ o }}
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <p class="intel-title font-medium">
                                    {{ ui.streams }}
                                </p>
                                <div class="mt-2 space-y-2">
                                    <div
                                        v-for="s in m.streams"
                                        :key="`${m.route}-${s.name}`"
                                        class="intel-section rounded-lg p-3"
                                    >
                                        <p class="text-sm font-semibold">
                                            {{ s.name }}
                                        </p>
                                        <ul
                                            class="mt-1 list-disc space-y-1 pl-5 text-muted-foreground"
                                        >
                                            <li
                                                v-for="i in s.items"
                                                :key="`${s.name}-${i}`"
                                            >
                                                {{ i }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </div>
</template>
