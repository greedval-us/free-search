<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { dashboard } from '@/routes';

type Param = {
    label: string;
    name: string;
    description: string;
    why: string;
    example: string;
};
type Stream = { name: string; items: string[] };
type ModuleDoc = {
    route: string | null;
    status: 'live' | 'planned';
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
        status: 'live',
        title: 'Telegram',
        purpose: 'Оперативный сбор и аналитика данных Telegram-каналов.',
        scenarios: [
            'Мониторинг инфополя',
            'Поиск аномалий активности',
            'Сбор хронологии доказательств',
        ],
        params: [
            {
                label: 'Канал или чат',
                name: 'chatUsername',
                description: 'Ссылка, username или короткое имя канала/чата.',
                why: 'Нужно, чтобы модуль понял, откуда собирать данные.',
                example: 'durov',
            },
            {
                label: 'Ключевая тема',
                name: 'keyword',
                description:
                    'Слово или фраза, по которым нужно искать сообщения.',
                why: 'Помогает отсеять лишний шум и сосредоточиться на нужной теме.',
                example: 'санкции',
            },
            {
                label: 'Автор сообщения',
                name: 'fromUsername/authorId',
                description: 'Имя пользователя или ID конкретного автора.',
                why: 'Полезно, когда нужно проверить сообщения одного источника.',
                example: '123456789',
            },
            {
                label: 'Период',
                name: 'dateFrom/dateTo',
                description:
                    'Интервал времени, в рамках которого нужен поиск или анализ.',
                why: 'Позволяет не смешивать старые и актуальные данные.',
                example: '2026-05-01 .. 2026-05-07',
            },
            {
                label: 'Объем выборки',
                name: 'limit',
                description:
                    'Сколько сообщений или результатов нужно показать.',
                why: 'Помогает выбрать баланс между скоростью работы и полнотой выборки.',
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
                    'Риск-флаги и объяснение подозрительной активности',
                    'Графики, топ-посты и активные авторы',
                    'Открытие/скачивание отчета',
                ],
            },
            {
                name: 'Парсер',
                items: [
                    'Статусы запуска и выполнения',
                    'Прогресс и счетчики обработки',
                    'Сбор по периодам и по ключевой теме',
                    'Выгрузки в Excel и JSON',
                ],
            },
        ],
    },
    {
        route: '/youtube',
        status: 'live',
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
                label: 'Что искать',
                name: 'q',
                description:
                    'Тема, фраза или название, по которым нужно найти видео, каналы или плейлисты.',
                why: 'Это основной ориентир для поиска.',
                example: 'osint tutorial',
            },
            {
                label: 'Тип результата',
                name: 'type',
                description:
                    'Что именно нужно найти: видео, канал или плейлист.',
                why: 'Помогает сразу получить релевантный формат результата.',
                example: 'video',
            },
            {
                label: 'Канал',
                name: 'channelId',
                description: 'ID канала, @handle или имя канала.',
                why: 'Используется, когда нужно искать или анализировать данные только по одному каналу.',
                example: 'UC_x5XG1OV2P6uZZ5FSM9Ttw',
            },
            {
                label: 'Видео',
                name: 'videoId',
                description: 'ID ролика или полная ссылка на него.',
                why: 'Нужно для точечной аналитики видео и сбора комментариев.',
                example: 'dQw4w9WgXcQ',
            },
            {
                label: 'Период публикации',
                name: 'publishedAfter/publishedBefore',
                description:
                    'Интервал, в который должны попадать найденные публикации.',
                why: 'Удобно для анализа свежей повестки или конкретного периода.',
                example: '2026-05-01 .. 2026-05-07',
            },
            {
                label: 'Окно аналитики',
                name: 'dateFrom/dateTo или periodDays',
                description:
                    'Период, за который нужно считать метрики и строить динамику.',
                why: 'Позволяет сравнивать активность на понятном временном отрезке.',
                example: '7',
            },
            {
                label: 'Объем и страницы результатов',
                name: 'limit/pageToken',
                description:
                    'Сколько результатов загружать и как переходить к следующим страницам.',
                why: 'Нужно для поэтапного просмотра больших выборок.',
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
                    'Результаты поиска: заголовок, описание, канал, дата публикации и превью',
                    'Метрики видео: просмотры, лайки, комментарии и уровень вовлеченности',
                    'Фильтры по региону, языку, безопасному поиску, длительности, качеству и субтитрам',
                    'Постраничная загрузка больших выборок',
                ],
            },
            {
                name: 'Аналитика',
                items: [
                    'Режимы video и channel',
                    'Суммарные метрики: просмотры, лайки, комментарии, средние и медианные значения',
                    'Распределения по времени, длительности, качеству и наличию субтитров',
                    'Лидеры по просмотрам, лайкам, комментариям и вовлеченности',
                    'Готовый отчет с возможностью скачать',
                ],
            },
            {
                name: 'Парсер',
                items: [
                    'Получение комментариев и ответов по выбранному видео',
                    'Сортировка по релевантности или дате и поиск внутри комментариев',
                    'Фоновый запуск, статус, остановка и прогресс выполнения',
                    'Экспорт результатов в Excel и JSON',
                ],
            },
        ],
    },
    {
        route: '/bluesky',
        status: 'live',
        title: 'Bluesky',
        purpose:
            'Поиск по постам и профилям Bluesky, а также аналитика по авторам и темам.',
        scenarios: [
            'Поиск обсуждений по теме',
            'Проверка профилей и связей',
            'Анализ постов, реакций и динамики',
        ],
        params: [
            {
                label: 'Что искать',
                name: 'q/text',
                description:
                    'Тема, фраза или ключевые слова для поиска по постам.',
                why: 'Помогает быстро найти релевантные обсуждения.',
                example: 'osint',
            },
            {
                label: 'Автор или профиль',
                name: 'author/actor',
                description: 'Имя профиля, handle или ссылка на автора.',
                why: 'Нужно для точечной проверки конкретного аккаунта.',
                example: '@analyst.bsky.social',
            },
            {
                label: 'Язык, домен, упоминания или теги',
                name: 'lang/domain/mentions/tag',
                description: 'Дополнительные фильтры для уточнения выборки.',
                why: 'Помогают быстрее сузить поиск до полезных результатов.',
                example: 'ru',
            },
        ],
        outputs: [
            'Посты, профили и связанные сущности',
            'Метрики вовлеченности и связи',
            'Аналитическая сводка по выбранной цели',
        ],
        streams: [
            {
                name: 'Поиск',
                items: [
                    'Посты по теме, тегам, доменам и упоминаниям',
                    'Профили авторов и базовые метрики',
                    'Раскрытие лайков, репостов и тредов',
                ],
            },
            {
                name: 'Аналитика',
                items: [
                    'Профиль аккаунта или хэштега',
                    'Топ-посты и заметные сигналы',
                    'Ключевые показатели по выбранной цели',
                ],
            },
        ],
    },
    {
        route: '/mastodon',
        status: 'live',
        title: 'Mastodon',
        purpose:
            'Поиск по аккаунтам, постам и хэштегам Mastodon с возможностью аналитики и разбора контекста.',
        scenarios: [
            'Проверка аккаунта или инстанса',
            'Анализ тематических тредов',
            'Мониторинг хэштегов и вовлеченности',
        ],
        params: [
            {
                label: 'Что искать',
                name: 'q',
                description: 'Тема, имя аккаунта, хэштег или текст для поиска.',
                why: 'Это базовая точка входа для результатов.',
                example: 'disinformation',
            },
            {
                label: 'Аккаунт',
                name: 'account',
                description:
                    'Аккаунт в формате handle, например @name@instance.',
                why: 'Нужен для анализа конкретного автора.',
                example: '@analyst@mastodon.social',
            },
            {
                label: 'Инстанс и язык',
                name: 'instance/lang',
                description: 'Фильтры по серверу Mastodon и языку контента.',
                why: 'Помогают сократить шум и уточнить источник данных.',
                example: 'mastodon.social',
            },
        ],
        outputs: [
            'Аккаунты, посты и хэштеги из поиска',
            'Контекст обсуждений и ответы',
            'Аналитика по аккаунту или хэштегу',
        ],
        streams: [
            {
                name: 'Поиск',
                items: [
                    'Посты, аккаунты и хэштеги',
                    'Загрузка контекста поста и связанных обсуждений',
                    'Переход к постам и подписчикам аккаунта',
                ],
            },
            {
                name: 'Аналитика',
                items: [
                    'Профиль аккаунта или хэштега',
                    'Топ-посты и базовые метрики',
                    'Более понятные состояния и подсказки для пользователя',
                ],
            },
        ],
    },
    {
        route: '/site-intel',
        status: 'live',
        title: 'Site Intel',
        purpose: 'Техническая разведка сайта и домена.',
        scenarios: [
            'Проверка доступности и безопасности',
            'SEO-аудит',
            'Аналитика домена',
        ],
        params: [
            {
                label: 'Сайт или домен',
                name: 'target/domain',
                description:
                    'Адрес сайта, страницы или домена, который нужно проверить.',
                why: 'Это основная цель технического анализа.',
                example: 'example.com',
            },
        ],
        outputs: [
            'Проверка DNS, SSL и защитных настроек сайта',
            'Сводка по домену и DNS-записям',
            'SEO-находки и рекомендации',
        ],
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
        route: '/news-media-intel',
        status: 'live',
        title: 'News & Media Intel',
        purpose: 'Мониторинг публичных упоминаний в новостях и медиа.',
        scenarios: ['Репутационный мониторинг', 'Отслеживание событий'],
        params: [
            {
                label: 'Что отслеживать',
                name: 'query',
                description: 'Тема, имя, бренд, организация или событие.',
                why: 'Определяет фокус мониторинга новостей и медиа.',
                example: 'санкции',
            },
        ],
        outputs: ['Лента упоминаний', 'Тональность', 'Таймлайн'],
        streams: [
            {
                name: 'Мониторинг',
                items: [
                    'Лента упоминаний',
                    'Сводка по темам',
                    'Тональность',
                    'Таймлайн',
                ],
            },
        ],
    },
    {
        route: '/shifr',
        status: 'live',
        title: 'Shifr',
        purpose:
            'Технический набор инструментов для преобразования артефактов.',
        scenarios: [
            'Хеширование и декодирование',
            'Извлечение IOC',
            'JWT и классические шифры',
        ],
        params: [
            {
                label: 'Режим и входные данные',
                name: 'tab/input',
                description:
                    'Какую операцию нужно выполнить и какие данные подать на вход.',
                why: 'От этого зависит, какой именно инструмент будет запущен.',
                example: 'hash + text',
            },
        ],
        outputs: ['Структурированный результат операции'],
        streams: [
            {
                name: 'Режимы',
                items: [
                    'Хеширование и HMAC',
                    'Преобразование и декодирование',
                    'IOC',
                    'Классические шифры',
                    'Проверка JWT',
                ],
            },
        ],
    },
];

const enModules: ModuleDoc[] = [
    {
        route: '/telegram',
        status: 'live',
        title: 'Telegram',
        purpose: 'Operational collection and analytics for Telegram channels.',
        scenarios: [
            'Information field monitoring',
            'Activity anomaly detection',
            'Evidence timeline collection',
        ],
        params: [
            {
                label: 'Channel or chat',
                name: 'chatUsername',
                description: 'Channel link, username, or short chat name.',
                why: 'Tells the module where to collect data from.',
                example: 'durov',
            },
            {
                label: 'Key topic',
                name: 'keyword',
                description: 'Word or phrase to look for in messages.',
                why: 'Helps reduce noise and keep the results focused.',
                example: 'sanctions',
            },
            {
                label: 'Message author',
                name: 'fromUsername/authorId',
                description: 'Specific username or author ID.',
                why: 'Useful when you need to review posts from one source.',
                example: '123456789',
            },
            {
                label: 'Time period',
                name: 'dateFrom/dateTo',
                description: 'Time window for search or analytics.',
                why: 'Keeps the dataset limited to the relevant period.',
                example: '2026-05-01 .. 2026-05-07',
            },
            {
                label: 'Result volume',
                name: 'limit',
                description: 'How many messages or results to load.',
                why: 'Balances speed and completeness.',
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
                    'Paginated comments',
                ],
            },
            {
                name: 'Analytics',
                items: [
                    'Period KPIs',
                    'Channel/group profile',
                    'Risk flags with explanations',
                    'Charts, top posts, and active voices',
                    'Open/download report',
                ],
            },
            {
                name: 'Parser',
                items: [
                    'Run and status states',
                    'Progress and processing counters',
                    'Collection by date range or key topic',
                    'Excel and JSON exports',
                ],
            },
        ],
    },
    {
        route: '/youtube',
        status: 'live',
        title: 'YouTube',
        purpose:
            'Search, analytics, and comment collection for YouTube videos and channels.',
        scenarios: [
            'Video agenda monitoring',
            'Channel and engagement analytics',
            'Comment collection for further validation',
        ],
        params: [
            {
                label: 'What to search for',
                name: 'q',
                description:
                    'Topic, phrase, or title used to find videos, channels, or playlists.',
                why: 'This is the main starting point for the search.',
                example: 'osint tutorial',
            },
            {
                label: 'Result type',
                name: 'type',
                description:
                    'Choose whether you need videos, channels, or playlists.',
                why: 'Keeps the output focused on the right content type.',
                example: 'video',
            },
            {
                label: 'Channel',
                name: 'channelId',
                description: 'Channel ID, @handle, or channel name.',
                why: 'Useful when search or analytics should stay within one channel.',
                example: 'UC_x5XG1OV2P6uZZ5FSM9Ttw',
            },
            {
                label: 'Video',
                name: 'videoId',
                description: 'Video ID or full video URL.',
                why: 'Needed for focused video analytics and comment collection.',
                example: 'dQw4w9WgXcQ',
            },
            {
                label: 'Publication period',
                name: 'publishedAfter/publishedBefore',
                description: 'Time window for the content you want to find.',
                why: 'Useful for recent trends or a specific event period.',
                example: '2026-05-01 .. 2026-05-07',
            },
            {
                label: 'Analytics window',
                name: 'dateFrom/dateTo or periodDays',
                description: 'Time range used to calculate metrics and trends.',
                why: 'Makes the analytics period explicit and easy to compare.',
                example: '7',
            },
            {
                label: 'Result size and pagination',
                name: 'limit/pageToken',
                description:
                    'How many results to load and how to move through large result sets.',
                why: 'Useful when reviewing data step by step.',
                example: '10',
            },
        ],
        outputs: [
            'Videos/channels/playlists from search',
            'Video/channel analytics summary',
            'Comments and replies',
            'HTML report and Excel/JSON exports',
        ],
        streams: [
            {
                name: 'Search',
                items: [
                    'Search results: title, description, channel, publication date, and preview',
                    'Video metrics: views, likes, comments, and engagement level',
                    'Filters: region, language, safe search, duration, quality, and captions',
                    'Paged loading for large result sets',
                ],
            },
            {
                name: 'Analytics',
                items: [
                    'Modes: video and channel',
                    'Aggregated metrics: views, likes, comments, averages, and median values',
                    'Distributions by time/duration/quality/captions',
                    'Leaders by views/likes/comments/engagement',
                    'Downloadable report',
                ],
            },
            {
                name: 'Parser',
                items: [
                    'Comments and replies for the selected video',
                    'Sorting by relevance or date and comment search',
                    'Background run/status/stop/progress',
                    'Excel and JSON exports',
                ],
            },
        ],
    },
    {
        route: '/bluesky',
        status: 'live',
        title: 'Bluesky',
        purpose:
            'Search across Bluesky posts and profiles with analytics for authors and topics.',
        scenarios: [
            'Topic discussion discovery',
            'Profile and relationship review',
            'Post, reaction, and activity analysis',
        ],
        params: [
            {
                label: 'What to search for',
                name: 'q/text',
                description:
                    'Topic, phrase, or keywords used to search across posts.',
                why: 'This is the main starting point for discovery.',
                example: 'osint',
            },
            {
                label: 'Author or profile',
                name: 'author/actor',
                description: 'Profile handle, account name, or profile URL.',
                why: 'Useful when reviewing one specific source.',
                example: '@analyst.bsky.social',
            },
            {
                label: 'Language, domain, mentions, or tag',
                name: 'lang/domain/mentions/tag',
                description: 'Extra filters that help narrow the result set.',
                why: 'Makes it easier to reduce noise quickly.',
                example: 'ru',
            },
        ],
        outputs: [
            'Posts, profiles, and linked entities',
            'Engagement and relationship signals',
            'Analytics summary for the selected target',
        ],
        streams: [
            {
                name: 'Search',
                items: [
                    'Posts by topic, tag, domain, and mentions',
                    'Author profiles with basic metrics',
                    'Expandable likes, reposts, and threads',
                ],
            },
            {
                name: 'Analytics',
                items: [
                    'Account or hashtag profile',
                    'Top posts and notable signals',
                    'Key metrics for the selected target',
                ],
            },
        ],
    },
    {
        route: '/mastodon',
        status: 'live',
        title: 'Mastodon',
        purpose:
            'Search across Mastodon accounts, posts, and hashtags with contextual review and analytics.',
        scenarios: [
            'Account or instance review',
            'Thread and discussion analysis',
            'Hashtag monitoring',
        ],
        params: [
            {
                label: 'What to search for',
                name: 'q',
                description:
                    'Topic, account, hashtag, or text query used for search.',
                why: 'This defines the starting point for the result set.',
                example: 'disinformation',
            },
            {
                label: 'Account',
                name: 'account',
                description:
                    'Account handle in a format such as @name@instance.',
                why: 'Needed for focused analysis of one author.',
                example: '@analyst@mastodon.social',
            },
            {
                label: 'Instance and language',
                name: 'instance/lang',
                description: 'Server and language filters for the dataset.',
                why: 'Helps narrow the search to the most relevant sources.',
                example: 'mastodon.social',
            },
        ],
        outputs: [
            'Accounts, posts, and hashtags from search',
            'Thread context and replies',
            'Account or hashtag analytics',
        ],
        streams: [
            {
                name: 'Search',
                items: [
                    'Posts, accounts, and hashtags',
                    'Context loading for posts and linked discussions',
                    'Access to account posts and followers',
                ],
            },
            {
                name: 'Analytics',
                items: [
                    'Account or hashtag profile',
                    'Top posts and basic metrics',
                    'Cleaner empty states and user-facing hints',
                ],
            },
        ],
    },
    {
        route: '/site-intel',
        status: 'live',
        title: 'Site Intel',
        purpose: 'Technical intelligence for website and domain.',
        scenarios: [
            'Availability and security checks',
            'SEO audit',
            'Domain analytics',
        ],
        params: [
            {
                label: 'Website or domain',
                name: 'target/domain',
                description: 'Website, page URL, or domain to inspect.',
                why: 'This is the target of the technical analysis.',
                example: 'example.com',
            },
        ],
        outputs: [
            'DNS, SSL, and website security checks',
            'Domain and DNS summary',
            'SEO findings and recommendations',
        ],
        streams: [
            {
                name: 'Modes',
                items: ['Site health', 'Domain lite', 'Analytics', 'SEO audit'],
            },
        ],
    },
    {
        route: '/news-media-intel',
        status: 'live',
        title: 'News & Media Intel',
        purpose: 'Public news and media mention monitoring.',
        scenarios: ['Reputation monitoring', 'Event tracking'],
        params: [
            {
                label: 'Monitoring target',
                name: 'query',
                description: 'Topic, person, brand, organization, or event.',
                why: 'Defines what the news monitoring should focus on.',
                example: 'sanctions',
            },
        ],
        outputs: ['Mentions feed', 'Sentiment snapshot', 'Timeline'],
        streams: [
            {
                name: 'Monitoring',
                items: [
                    'Mentions feed',
                    'Topic summary',
                    'Sentiment snapshot',
                    'Timeline',
                ],
            },
        ],
    },
    {
        route: '/shifr',
        status: 'live',
        title: 'Shifr',
        purpose: 'Technical toolkit for artifact transformations.',
        scenarios: ['Hash/decode', 'IOC extraction', 'JWT and classic ciphers'],
        params: [
            {
                label: 'Mode and input data',
                name: 'tab/input',
                description:
                    'Choose the operation and provide the source data.',
                why: 'This determines which tool runs and how it behaves.',
                example: 'hash + text',
            },
        ],
        outputs: ['Structured operation result'],
        streams: [
            {
                name: 'Modes',
                items: [
                    'Hashing and HMAC',
                    'Transform and decode',
                    'IOC extraction',
                    'Classic ciphers',
                    'JWT checks',
                ],
            },
        ],
    },
];

const modules = computed(() => (isRu.value ? ruModules : enModules));

const ui = computed(() => ({
    title: isRu.value ? 'Вики модулей' : 'Modules Wiki',
    subtitle: isRu.value
        ? 'Краткое и понятное объяснение: что делает каждый модуль, когда его использовать и что нужно указать на входе.'
        : 'A plain-language guide to what each module does, when to use it, and what information to provide.',
    purpose: isRu.value ? 'Зачем нужен модуль' : 'Purpose',
    scenarios: isRu.value ? 'Когда использовать' : 'When to use',
    params: isRu.value ? 'Что нужно указать' : 'What to provide',
    outputs: isRu.value ? 'Что вы получите' : 'What you get',
    streams: isRu.value
        ? 'Что модуль показывает и формирует'
        : 'What the module shows and produces',
    open: isRu.value ? 'Открыть модуль' : 'Open module',
    planned: isRu.value ? 'В разработке' : 'In development',
    example: isRu.value ? 'Пример' : 'Example',
}));

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                titleKey: 'navigation.dashboard',
                href: dashboard(),
            },
            {
                title: 'Wiki / Modules',
                titleKey: 'navigation.modulesWiki',
                href: '/wiki/modules',
            },
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
                        :key="m.route ?? `${m.title}-${m.status}`"
                        class="intel-surface rounded-xl p-4"
                    >
                        <div
                            class="mb-3 flex items-start justify-between gap-3"
                        >
                            <div>
                                <h2 class="text-lg font-semibold">
                                    {{ m.title }}
                                </h2>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ m.purpose }}
                                </p>
                            </div>
                            <a
                                v-if="m.status === 'live' && m.route"
                                :href="m.route"
                                class="inline-flex rounded-md border border-primary/30 bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                            >
                                {{ ui.open }}
                            </a>
                            <span
                                v-else
                                class="inline-flex rounded-md border border-border/70 bg-background/60 px-2.5 py-1 text-xs font-medium text-muted-foreground"
                            >
                                {{ ui.planned }}
                            </span>
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
                                            {{ p.label }}
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
