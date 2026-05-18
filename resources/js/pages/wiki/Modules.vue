<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { dashboard } from '@/routes';

type ModuleParam = {
    name: string;
    description: string;
    why: string;
    example: string;
};

type ModuleDataStream = {
    name: string;
    items: string[];
};

type ModuleDoc = {
    key: string;
    route: string;
    title: string;
    purpose: string;
    scenarios: string[];
    params: ModuleParam[];
    outputs: string[];
    dataStreams: ModuleDataStream[];
};

const { locale } = useI18n();
const isRu = computed(() => locale.value === 'ru');

const modules: ModuleDoc[] = [
    {
        key: 'telegram',
        route: '/telegram',
        title: 'Telegram',
        purpose: 'Оперативный сбор и аналитика данных каналов Telegram.',
        scenarios: ['Мониторинг инфополя', 'Выявление аномалий активности', 'Сбор доказательной хронологии'],
        params: [
            { name: 'chatUsername', description: 'Имя канала/чата.', why: 'Определяет источник данных.', example: 'durov' },
            { name: 'keyword', description: 'Ключевое слово/фраза.', why: 'Фокусирует поиск и снижает шум.', example: 'санкции' },
            { name: 'fromUsername/authorId', description: 'Фильтр по автору.', why: 'Нужен для таргетного анализа конкретного автора.', example: '123456789' },
            { name: 'dateFrom/dateTo', description: 'Период анализа.', why: 'Ограничивает выборку нужным временным окном.', example: '2026-05-01 .. 2026-05-07' },
            { name: 'limit', description: 'Лимит сообщений.', why: 'Баланс между скоростью и полнотой.', example: '20' },
        ],
        outputs: ['Результаты по сообщениям', 'Метрики вовлеченности', 'Экспортируемые отчеты/выгрузки'],
        dataStreams: [
            {
                name: 'Search',
                items: [
                    'Список сообщений: текст, дата, author id, views, forwards, replies',
                    'Данные медиа: тип, превью/ссылка',
                    'Реакции и подарки + sender ids',
                    'Комментарии к постам с пагинацией',
                ],
            },
            {
                name: 'Analytics',
                items: [
                    'KPI summary за период',
                    'Профиль канала/группы: тип, участники, ограничения, verified/scam flags',
                    'Anti-fraud блок: risk score, triggers, reasons',
                    'Графики: тренды, funnel, audience, distribution, top posts, opinion leaders',
                    'Формирование/скачивание отчета',
                ],
            },
            {
                name: 'Parser',
                items: [
                    'Статус запуска: idle/messages/comments/finishing/completed/failed/stopped',
                    'Прогресс в %, счетчики обработанных сообщений и комментариев',
                    'Сбор по периоду (day/week/month/custom), keyword-режим',
                    'Выгрузки: Excel и JSON',
                ],
            },
        ],
    },
    {
        key: 'username',
        route: '/username',
        title: 'Username',
        purpose: 'Поиск следов username на платформах.',
        scenarios: ['Поиск псевдонимов', 'Проверка имперсонации', 'Кросс-платформенный pivot'],
        params: [
            { name: 'username', description: 'Искомый ник.', why: 'Базовая сущность поиска.', example: 'greedval' },
            { name: 'autorun', description: 'Автозапуск.', why: 'Ускоряет повторные проверки.', example: '1' },
        ],
        outputs: ['Сводка найдено/не найдено', 'Confidence показатели', 'Граф связей'],
        dataStreams: [
            { name: 'Search', items: ['Статусы по платформам', 'Ссылки на профили', 'Категории и регионы платформ'] },
            { name: 'Analytics', items: ['Средний confidence', 'Варианты похожих username + причина', 'Entity graph: nodes/edges'] },
        ],
    },
    {
        key: 'site-intel',
        route: '/site-intel',
        title: 'Site Intel',
        purpose: 'Техническая разведка сайта и домена.',
        scenarios: ['Быстрый аудит цели', 'Проверка базовой безопасности', 'SEO/техпроверка'],
        params: [
            { name: 'target', description: 'URL или домен.', why: 'Цель проверки.', example: 'https://example.com' },
            { name: 'domain', description: 'Домен для domain-lite.', why: 'WHOIS/DNS без полного обхода.', example: 'example.com' },
            { name: 'crawl_limit', description: 'Лимит обхода.', why: 'Контроль времени и нагрузки.', example: '8' },
            { name: 'platform_type', description: 'Тип платформы.', why: 'Точность SEO-аудита.', example: 'auto' },
        ],
        outputs: ['Технический health отчет', 'DNS/WHOIS/SSL сводки', 'SEO findings'],
        dataStreams: [
            { name: 'Site Health', items: ['HTTP chain и final status', 'DNS A/AAAA', 'SSL issuer/subject/validity', 'Security headers coverage'] },
            { name: 'Domain Lite', items: ['WHOIS summary', 'DNS stack (A/AAAA/NS/MX/SPF/DMARC)', 'Risk score'] },
            { name: 'Analytics', items: ['Агрегированные техметрики цели'] },
            { name: 'SEO Audit', items: ['Результаты обхода страниц', 'Проблемы on-page/tech SEO'] },
        ],
    },
    {
        key: 'fio',
        route: '/fio',
        title: 'FIO',
        purpose: 'Поиск по ФИО и персональным связям.',
        scenarios: ['Первичный поиск персоны', 'Уточнение по контексту', 'Проверка гипотез'],
        params: [
            { name: 'full_name', description: 'ФИО.', why: 'Основной ключ поиска.', example: 'Иванов Иван Иванович' },
            { name: 'qualifier', description: 'Уточнение.', why: 'Снижает шум для частых ФИО.', example: 'военный' },
        ],
        outputs: ['Список релевантных упоминаний', 'Сводные сигналы по персоне'],
        dataStreams: [{ name: 'Lookup', items: ['Упоминания по ФИО', 'Ссылки/сниппеты источников', 'Структурированные признаки'] }],
    },
    {
        key: 'company-intel',
        route: '/company-intel',
        title: 'Company Intel',
        purpose: 'Разведка открытых данных о компании.',
        scenarios: ['Due diligence', 'Enrichment сущности', 'Проверка цифрового следа'],
        params: [
            { name: 'query', description: 'Компания/бренд/домен.', why: 'Определяет объект проверки.', example: 'openai.com' },
            { name: 'locale', description: 'Локаль.', why: 'Релевантность источников.', example: 'ru' },
        ],
        outputs: ['Профиль компании', 'Публичные связи и упоминания'],
        dataStreams: [{ name: 'Lookup', items: ['Company profile blocks', 'Reference links', 'Related entities/signals'] }],
    },
    {
        key: 'document-intel',
        route: '/document-intel',
        title: 'Document Intel',
        purpose: 'Поиск документов и извлечение сигналов из них.',
        scenarios: ['Сбор доказательной базы', 'Поиск публичных файлов', 'Верификация источников'],
        params: [
            { name: 'query', description: 'Домен/ФИО/компания/ключ.', why: 'База для поиска документов.', example: 'hh.ru' },
            { name: 'locale', description: 'Локаль.', why: 'Фокус на нужном языке и регионе.', example: 'ru' },
        ],
        outputs: ['Ссылки на документы', 'Метаданные', 'Сводные сигналы'],
        dataStreams: [{ name: 'Lookup', items: ['Document links', 'Title/source/type metadata', 'Extracted insights'] }],
    },
    {
        key: 'email-intel',
        route: '/email-intel',
        title: 'Email Intel',
        purpose: 'Проверка цифровых следов email.',
        scenarios: ['Валидация контакта', 'Lead triage', 'Первичная оценка риска'],
        params: [
            { name: 'email', description: 'Email адрес.', why: 'Основная сущность проверки.', example: 'name@example.com' },
            { name: 'tab', description: 'Режим модуля.', why: 'Открывает нужный подпроцесс.', example: 'search|analytics|bulk|domain' },
        ],
        outputs: ['Сигналы по адресу/домену', 'Контекст для дальнейших шагов'],
        dataStreams: [
            { name: 'Search', items: ['Lookup по одному адресу'] },
            { name: 'Analytics', items: ['Агрегированный аналитический блок по email'] },
            { name: 'Bulk', items: ['Пакетная проверка списка email + сводные итоги'] },
            { name: 'Domain', items: ['Сигналы по домену почты'] },
        ],
    },
    {
        key: 'domain-infra-intel',
        route: '/domain-infra-intel',
        title: 'Domain & Infra Intel',
        purpose: 'Картирование инфраструктуры домена.',
        scenarios: ['Кластеризация инфраструктуры', 'Поиск связанных активов', 'Оценка риска хостинга'],
        params: [
            { name: 'domain', description: 'Домен/URL.', why: 'Стартовая точка infra pivot.', example: 'habr.com' },
            { name: 'locale', description: 'Локаль.', why: 'Формат текстовой части результатов.', example: 'ru' },
        ],
        outputs: ['IP/ASN/WHOIS/RDAP данные', 'Соседние домены', 'Сертификатные следы'],
        dataStreams: [{ name: 'Lookup', items: ['Resolved IPs', 'ASN/network owner', 'RDAP/WHOIS payloads', 'Neighbor domains', 'CT sightings'] }],
    },
    {
        key: 'news-media-intel',
        route: '/news-media-intel',
        title: 'News & Media Intel',
        purpose: 'Мониторинг медиа-упоминаний.',
        scenarios: ['Репутационный мониторинг', 'Отслеживание инфоповодов', 'Поиск всплесков'],
        params: [
            { name: 'query', description: 'Тема/персона/бренд.', why: 'Объект мониторинга.', example: 'война в украине' },
            { name: 'locale', description: 'Локаль агрегации.', why: 'Фокус на нужном языковом контуре.', example: 'ru' },
        ],
        outputs: ['Лента упоминаний', 'Тематические кластеры', 'Тональность/таймлайн'],
        dataStreams: [{ name: 'Lookup', items: ['Mentions feed', 'Topic summary', 'Sentiment breakdown', 'Timeline by date'] }],
    },
    {
        key: 'shifr',
        route: '/shifr',
        title: 'Shifr',
        purpose: 'Технический toolbox для трансформаций и крипто-утилит.',
        scenarios: ['Декодинг артефактов', 'Извлечение IOC', 'Работа с токенами и классическими шифрами'],
        params: [
            { name: 'tab', description: 'Режим.', why: 'Выбирает нужный инструмент.', example: 'hash|transform|ioc|classic|jwt' },
            { name: 'input', description: 'Входной артефакт.', why: 'Данные для обработки.', example: 'eyJhbGciOi...' },
            { name: 'algorithm/operation', description: 'Алгоритм/операция.', why: 'Логика преобразования.', example: 'sha256|base64Decode|caesar' },
        ],
        outputs: ['Структурированные результаты операций'],
        dataStreams: [
            { name: 'Hash/HMAC', items: ['Digest/HMAC value + metadata'] },
            { name: 'Transform', items: ['Original/result values'] },
            { name: 'IOC', items: ['Extracted emails/urls/domains/ips/telegram usernames'] },
            { name: 'Classic', items: ['Cipher output + params'] },
            { name: 'JWT', items: ['Decoded header/payload + signature/time checks'] },
        ],
    },
];

const ui = computed(() => ({
    pageTitle: isRu.value ? 'Вики: Модули' : 'Wiki: Modules',
    heading: isRu.value ? 'Подробная вики по модулям' : 'Detailed module wiki',
    subtitle: isRu.value
        ? 'Описание параметров и реальных данных, которые формируются в каждом модуле и его вкладках.'
        : 'Parameters and produced datasets for each module and tab.',
    sections: {
        purpose: isRu.value ? 'Зачем нужен модуль' : 'Purpose',
        scenarios: isRu.value ? 'Когда использовать' : 'When to use',
        parameters: isRu.value ? 'Параметры' : 'Parameters',
        outputs: isRu.value ? 'Результат' : 'Output',
        producedData: isRu.value ? 'Какие данные формируются' : 'Produced data',
        example: isRu.value ? 'Пример' : 'Example',
        open: isRu.value ? 'Открыть модуль' : 'Open module',
    },
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
    <Head :title="ui.pageTitle" />

    <div class="mx-auto w-full max-w-7xl space-y-4 p-4 sm:p-6">
        <section class="intel-panel-strong relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-primary/10 via-transparent to-chart-2/10" />
            <div class="relative">
                <h1 class="text-xl font-semibold tracking-tight sm:text-2xl">{{ ui.heading }}</h1>
                <p class="mt-2 max-w-3xl text-sm text-muted-foreground">{{ ui.subtitle }}</p>
            </div>
        </section>

        <section class="intel-panel p-3 sm:p-4">
            <div class="telegram-scroll max-h-[72vh] overflow-y-auto pr-1 sm:pr-2 [scrollbar-gutter:stable]">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <article v-for="module in modules" :key="module.key" class="intel-surface rounded-xl p-4 shadow-sm transition-colors hover:bg-background/75">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <p class="text-[11px] uppercase tracking-[0.12em] text-muted-foreground">{{ module.key }}</p>
                                <h2 class="text-lg font-semibold">{{ module.title }}</h2>
                            </div>
                            <a :href="module.route" class="inline-flex rounded-md border border-primary/30 bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary transition hover:bg-primary/15">
                                {{ ui.sections.open }}
                            </a>
                        </div>

                        <div class="space-y-4 text-sm">
                            <div>
                                <p class="intel-title font-medium">{{ ui.sections.purpose }}</p>
                                <p class="mt-1 text-muted-foreground">{{ module.purpose }}</p>
                            </div>

                            <div>
                                <p class="intel-title font-medium">{{ ui.sections.scenarios }}</p>
                                <ul class="mt-1 list-disc space-y-1 pl-5 text-muted-foreground">
                                    <li v-for="scenario in module.scenarios" :key="scenario">{{ scenario }}</li>
                                </ul>
                            </div>

                            <div>
                                <p class="intel-title font-medium">{{ ui.sections.parameters }}</p>
                                <div class="mt-2 space-y-2">
                                    <div v-for="param in module.params" :key="`${module.key}-${param.name}`" class="intel-section rounded-lg p-3">
                                        <p class="text-sm font-semibold">{{ param.name }}</p>
                                        <p class="mt-1 text-muted-foreground">{{ param.description }}</p>
                                        <p class="mt-1 text-muted-foreground">{{ param.why }}</p>
                                        <p class="mt-1 text-xs text-primary">{{ ui.sections.example }}: <span class="font-mono">{{ param.example }}</span></p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p class="intel-title font-medium">{{ ui.sections.outputs }}</p>
                                <ul class="mt-1 list-disc space-y-1 pl-5 text-muted-foreground">
                                    <li v-for="output in module.outputs" :key="output">{{ output }}</li>
                                </ul>
                            </div>

                            <div>
                                <p class="intel-title font-medium">{{ ui.sections.producedData }}</p>
                                <div class="mt-2 space-y-2">
                                    <div v-for="stream in module.dataStreams" :key="`${module.key}-${stream.name}`" class="intel-section rounded-lg p-3">
                                        <p class="text-sm font-semibold">{{ stream.name }}</p>
                                        <ul class="mt-1 list-disc space-y-1 pl-5 text-muted-foreground">
                                            <li v-for="item in stream.items" :key="`${stream.name}-${item}`">{{ item }}</li>
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
