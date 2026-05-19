<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Лексикон квалификаторов FIO
    |--------------------------------------------------------------------------
    |
    | Ключ — семантическая роль, значения — синонимы/маркеры.
    | Используется для:
    | - расширения поисковых запросов у провайдеров
    | - совпадений по квалификаторам в confidence scoring
    |
    */
    'qualifier_lexicon' => [
        'leader' => ['руководитель', 'директор', 'начальник', 'управляющий', 'менеджер', 'manager', 'director', 'head', 'executive', 'ceo'],
        'military' => ['военный', 'военнослужащий', 'армия', 'офицер', 'генерал', 'army', 'military', 'officer', 'veteran', 'defense'],
        'politics' => ['политик', 'депутат', 'министр', 'сенатор', 'politician', 'minister', 'senator', 'government', 'state official'],
        'business' => ['предприниматель', 'бизнесмен', 'основатель', 'инвестор', 'entrepreneur', 'businessman', 'founder', 'investor'],
        'legal' => ['юрист', 'адвокат', 'прокурор', 'lawyer', 'attorney', 'legal counsel'],
        'medical' => ['врач', 'доктор', 'хирург', 'медик', 'doctor', 'physician', 'surgeon', 'medical'],
        'law_enforcement' => ['полиция', 'полицейский', 'следователь', 'оперативник', 'правоохранитель', 'police', 'detective', 'investigator', 'law enforcement'],
        'intelligence' => ['разведка', 'контрразведка', 'спецслужбы', 'агент', 'intelligence', 'counterintelligence', 'special services', 'operative agent'],
        'security' => ['безопасность', 'служба безопасности', 'охранник', 'security', 'security officer', 'security specialist'],
        'it' => ['айти', 'it', 'разработчик', 'программист', 'инженер', 'developer', 'software engineer', 'architect', 'cto'],
        'finance' => ['финансы', 'финансист', 'банкир', 'бухгалтер', 'экономист', 'finance', 'banker', 'accountant', 'economist', 'cfo'],
        'education' => ['преподаватель', 'учитель', 'профессор', 'доцент', 'educator', 'teacher', 'lecturer', 'professor', 'academic'],
        'science' => ['ученый', 'исследователь', 'научный сотрудник', 'scientist', 'researcher', 'analyst', 'r&d'],
        'media' => ['журналист', 'редактор', 'репортер', 'корреспондент', 'journalist', 'editor', 'reporter', 'media'],
        'pr_marketing' => ['маркетолог', 'пиар', 'пресс-секретарь', 'smm', 'marketing', 'pr', 'communications', 'press secretary', 'brand manager'],
        'judiciary' => ['судья', 'судебный', 'суд', 'judge', 'judicial', 'court'],
        'civil_service' => ['чиновник', 'госслужащий', 'администрация', 'муниципалитет', 'civil servant', 'public official', 'administration'],
        'religion' => ['священник', 'имам', 'пастор', 'духовенство', 'clergy', 'priest', 'imam', 'pastor', 'religious leader'],
        'transport' => ['пилот', 'водитель', 'моряк', 'капитан', 'pilot', 'driver', 'seafarer', 'captain', 'aviation'],
        'sports' => ['спортсмен', 'тренер', 'футболист', 'боксер', 'athlete', 'coach', 'player', 'sportsman', 'olympian'],
        'culture' => ['артист', 'актер', 'музыкант', 'режиссер', 'писатель', 'artist', 'actor', 'musician', 'director', 'writer'],
        'real_estate' => ['риелтор', 'девелопер', 'застройщик', 'real estate', 'realtor', 'developer', 'construction executive'],
        'energy' => ['нефть', 'газ', 'энергетик', 'энергетика', 'oil', 'gas', 'energy', 'power engineer'],
        'ngo' => ['нко', 'благотворительность', 'общественный деятель', 'ngo', 'non-profit', 'charity', 'activist'],
        'cybercrime' => ['киберпреступник', 'мошенник', 'фрод', 'скам', 'cybercriminal', 'fraudster', 'scammer', 'phisher'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Надёжность источников
    |--------------------------------------------------------------------------
    |
    | Относительный коэффициент доверия по source key.
    | Применяется как вес в итоговой confidence-оценке.
    |
    */
    'source_reliability' => [
        'duckduckgo' => 0.95,
        'bing' => 0.90,
        'googlenews' => 0.85,
        'reddit' => 0.75,
        'yahoo' => 0.80,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dork-поиск
    |--------------------------------------------------------------------------
    |
    | Шаблоны запросов, которые дополняют базовый запрос.
    | Доступные плейсхолдеры:
    | - {name}       : ФИО в кавычках
    | - {query}      : базовый запрос (ФИО + квалификаторы)
    | - {qualifiers} : OR-выражение квалификаторов (может быть пустым)
    |
    */
    'network_dork_search' => [
        'max_queries' => (int) env('OSINT_FIO_DORK_MAX_QUERIES', 6),
        'templates' => [
            '{name}',
            '{name} {qualifiers}',
            '{name} (intext:{name} OR intitle:{name}) {qualifiers}',
            '{name} (site:linkedin.com OR site:facebook.com OR site:vk.com OR site:ok.ru OR site:instagram.com OR site:x.com OR site:t.me) {qualifiers}',
            '{name} (site:gov OR site:edu OR site:mil) {qualifiers}',
            '{name} (resume OR biography OR profile OR contact) {qualifiers}',
        ],
        'engines' => [
            [
                'source' => 'duckduckgo',
                'url_template' => 'https://html.duckduckgo.com/html/?q={query}',
                'headers' => [],
            ],
            [
                'source' => 'bing',
                'url_template' => 'https://www.bing.com/search?format=rss&q={query}',
                'headers' => ['Accept' => 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8'],
            ],
            [
                'source' => 'googlenews',
                'url_template' => 'https://news.google.com/rss/search?q={query}&hl=ru&gl=RU&ceid=RU:ru',
                'headers' => ['Accept' => 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8'],
            ],
            [
                'source' => 'reddit',
                'url_template' => 'https://www.reddit.com/search.rss?q={query}',
                'headers' => ['Accept' => 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8'],
            ],
            [
                'source' => 'yahoo',
                'url_template' => 'https://search.yahoo.com/rss?p={query}',
                'headers' => ['Accept' => 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8'],
            ],
        ],
    ],
];
