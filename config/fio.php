<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FIO Qualifier Lexicon
    |--------------------------------------------------------------------------
    |
    | Key is a semantic role label, values are synonyms used for:
    | - query expansion in providers
    | - qualifier matching in confidence scoring
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
    | Source Reliability
    |--------------------------------------------------------------------------
    |
    | Relative trust score by source key. Used in confidence weighting.
    |
    */
    'source_reliability' => [
        'duckduckgo' => 0.95,
        'bing' => 0.90,
    ],
];
