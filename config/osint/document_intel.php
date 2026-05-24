<?php

return [
    'http' => [
        'user_agent' => env('OSINT_DOCUMENT_INTEL_HTTP_USER_AGENT', 'FreeSearch-DocumentIntel/1.0'),
        'timeout_seconds' => (int) env('OSINT_DOCUMENT_INTEL_HTTP_TIMEOUT', 10),
    ],
    'discovery' => [
        'max_documents' => (int) env('OSINT_DOCUMENT_INTEL_MAX_DOCUMENTS', 20),
        'max_file_size_bytes' => (int) env('OSINT_DOCUMENT_INTEL_MAX_FILE_SIZE_BYTES', 5000000),
        'extensions' => ['pdf', 'docx', 'xlsx', 'pptx'],
    ],
    'extraction' => [
        'max_items_per_type' => (int) env('OSINT_DOCUMENT_INTEL_MAX_ITEMS_PER_TYPE', 15),
    ],
    'risk' => [
        'thresholds' => [
            'medium' => (int) env('OSINT_DOCUMENT_INTEL_RISK_MEDIUM', 30),
            'high' => (int) env('OSINT_DOCUMENT_INTEL_RISK_HIGH', 60),
        ],
        'weights' => [
            'author_exposed' => (int) env('OSINT_DOCUMENT_INTEL_RISK_AUTHOR_EXPOSED', 20),
            'email_exposed' => (int) env('OSINT_DOCUMENT_INTEL_RISK_EMAIL_EXPOSED', 15),
            'internal_paths_exposed' => (int) env('OSINT_DOCUMENT_INTEL_RISK_INTERNAL_PATHS_EXPOSED', 25),
            'legacy_software_hint' => (int) env('OSINT_DOCUMENT_INTEL_RISK_LEGACY_SOFTWARE_HINT', 15),
        ],
    ],
];
