<?php

return [
    'errors' => [
        'invalid_options' => 'Select valid search settings.',
        'query_routing' => 'Use text, quotes, site:domain and minus exclusions. Select engines and language in filters instead of !engine and :language commands.',
        'domain' => 'Enter a public domain or HTTP/HTTPS URL without credentials or a port.',
        'duplicate_brand' => 'A competitor name must differ from your brand.',
    ],
    'report' => [
        'title' => 'News and media: SEO and marketing analytics',
        'query' => 'Query', 'checked_at' => 'Checked at', 'language' => 'Search language', 'period' => 'Time filter',
        'methodology' => 'All metrics describe the collected SearXNG sample. They are not search volume, traffic, complete web coverage or Google rankings. Sentiment uses a title and snippet dictionary; topics and questions are heuristics. The news timeline includes only documents with a known date no later than this check.',
        'partial' => 'The sample is partial: some engines are unavailable or a page, time or result limit was reached.',
        'mentions' => 'Sampled documents', 'publishers' => 'Publishers and websites', 'known_dates' => 'Known dates',
        'unknown_dates' => 'Undated', 'freshness' => 'Last 7 days among dated documents',
        'brand' => 'Brand and competitors', 'entity' => 'Name', 'count' => 'Documents', 'share' => 'Share, %',
        'brand_method' => 'Names are matched against titles and snippets in one shared sample. Share is matching documents for a name divided by total matches across the selected names. A document can mention multiple brands. Absence from the sample does not prove absence from the web.',
        'sentiment' => 'Sentiment: + / neutral / −', 'topics' => 'Content topics', 'questions' => 'Audience questions',
        'suggestions' => 'Search suggestions', 'corrections' => 'Spelling alternatives', 'answers' => 'Search answers',
        'infoboxes' => 'Search infoboxes',
        'schedule' => [
            'title' => 'Scheduled report', 'scheduled_for' => 'Scheduled check', 'timezone' => 'Time zone',
            'frequency' => 'Delivery frequency', 'basis' => 'This is the saved search sample at the actual check time. Delivery frequency does not define the publication period; the search time filter controls it.',
            'intervals' => ['1' => 'Every day', '3' => 'Every 3 days', '7' => 'Every 7 days', 'month' => 'Every month'],
        ],
        'periods' => ['all' => 'All time', 'day' => 'Last day', 'week' => 'Last week', 'month' => 'Last month', 'year' => 'Last year'],
        'visibility' => 'Domain presence in web results', 'domain' => 'Domain', 'domain_matches' => 'Matching domain pages',
        'best_position' => 'First ordinal in collected results', 'visibility_method' => 'The ordinal is the order of merged SearXNG results, not a ranking in an individual engine. Matching covers the domain and its subdomains; a missing result does not mean a page is not indexed.',
        'coverage' => 'Search coverage', 'news' => 'News', 'general' => 'Web', 'pages' => 'Loaded / requested pages',
        'engines' => 'Engines', 'unresponsive' => 'Unavailable engines', 'unavailable' => 'Unavailable', 'limited' => 'Limited',
        'timeline' => 'News publications by date', 'date' => 'Date', 'documents' => 'Documents and primary sources',
        'source' => 'Publisher', 'title_column' => 'Title', 'url' => 'URL', 'position' => 'Ordinal', 'no_data' => 'No data',
        'opportunities' => 'Suggested actions',
        'actions' => [
            'topic_coverage' => 'Create content about this topic', 'answer_question' => 'Answer an audience question',
            'brand_presence' => 'Review brand presence for this query', 'publisher_outreach' => 'Explore this publisher for PR and placements',
        ],
    ],
];
