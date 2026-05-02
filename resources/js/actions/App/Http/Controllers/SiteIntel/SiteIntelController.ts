import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:28
 * @route '/site-intel/site-health'
 */
export const siteHealth = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: siteHealth.url(options),
    method: 'get',
})

siteHealth.definition = {
    methods: ["get","head"],
    url: '/site-intel/site-health',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:28
 * @route '/site-intel/site-health'
 */
siteHealth.url = (options?: RouteQueryOptions) => {
    return siteHealth.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:28
 * @route '/site-intel/site-health'
 */
siteHealth.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: siteHealth.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:28
 * @route '/site-intel/site-health'
 */
siteHealth.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: siteHealth.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:28
 * @route '/site-intel/site-health'
 */
    const siteHealthForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: siteHealth.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:28
 * @route '/site-intel/site-health'
 */
        siteHealthForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: siteHealth.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:28
 * @route '/site-intel/site-health'
 */
        siteHealthForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: siteHealth.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    siteHealth.form = siteHealthForm
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::domainLite
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:42
 * @route '/site-intel/domain-lite'
 */
export const domainLite = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: domainLite.url(options),
    method: 'get',
})

domainLite.definition = {
    methods: ["get","head"],
    url: '/site-intel/domain-lite',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::domainLite
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:42
 * @route '/site-intel/domain-lite'
 */
domainLite.url = (options?: RouteQueryOptions) => {
    return domainLite.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::domainLite
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:42
 * @route '/site-intel/domain-lite'
 */
domainLite.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: domainLite.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::domainLite
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:42
 * @route '/site-intel/domain-lite'
 */
domainLite.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: domainLite.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::domainLite
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:42
 * @route '/site-intel/domain-lite'
 */
    const domainLiteForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: domainLite.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::domainLite
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:42
 * @route '/site-intel/domain-lite'
 */
        domainLiteForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: domainLite.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::domainLite
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:42
 * @route '/site-intel/domain-lite'
 */
        domainLiteForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: domainLite.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    domainLite.form = domainLiteForm
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::analytics
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:56
 * @route '/site-intel/analytics'
 */
export const analytics = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: analytics.url(options),
    method: 'get',
})

analytics.definition = {
    methods: ["get","head"],
    url: '/site-intel/analytics',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::analytics
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:56
 * @route '/site-intel/analytics'
 */
analytics.url = (options?: RouteQueryOptions) => {
    return analytics.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::analytics
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:56
 * @route '/site-intel/analytics'
 */
analytics.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: analytics.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::analytics
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:56
 * @route '/site-intel/analytics'
 */
analytics.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: analytics.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::analytics
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:56
 * @route '/site-intel/analytics'
 */
    const analyticsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: analytics.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::analytics
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:56
 * @route '/site-intel/analytics'
 */
        analyticsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: analytics.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::analytics
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:56
 * @route '/site-intel/analytics'
 */
        analyticsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: analytics.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    analytics.form = analyticsForm
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoAudit
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:72
 * @route '/site-intel/seo-audit'
 */
export const seoAudit = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: seoAudit.url(options),
    method: 'get',
})

seoAudit.definition = {
    methods: ["get","head"],
    url: '/site-intel/seo-audit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoAudit
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:72
 * @route '/site-intel/seo-audit'
 */
seoAudit.url = (options?: RouteQueryOptions) => {
    return seoAudit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoAudit
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:72
 * @route '/site-intel/seo-audit'
 */
seoAudit.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: seoAudit.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoAudit
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:72
 * @route '/site-intel/seo-audit'
 */
seoAudit.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: seoAudit.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoAudit
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:72
 * @route '/site-intel/seo-audit'
 */
    const seoAuditForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: seoAudit.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoAudit
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:72
 * @route '/site-intel/seo-audit'
 */
        seoAuditForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: seoAudit.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoAudit
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:72
 * @route '/site-intel/seo-audit'
 */
        seoAuditForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: seoAudit.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    seoAudit.form = seoAuditForm
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoReport
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:86
 * @route '/site-intel/seo-report'
 */
export const seoReport = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: seoReport.url(options),
    method: 'get',
})

seoReport.definition = {
    methods: ["get","head"],
    url: '/site-intel/seo-report',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoReport
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:86
 * @route '/site-intel/seo-report'
 */
seoReport.url = (options?: RouteQueryOptions) => {
    return seoReport.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoReport
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:86
 * @route '/site-intel/seo-report'
 */
seoReport.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: seoReport.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoReport
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:86
 * @route '/site-intel/seo-report'
 */
seoReport.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: seoReport.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoReport
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:86
 * @route '/site-intel/seo-report'
 */
    const seoReportForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: seoReport.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoReport
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:86
 * @route '/site-intel/seo-report'
 */
        seoReportForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: seoReport.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::seoReport
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:86
 * @route '/site-intel/seo-report'
 */
        seoReportForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: seoReport.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    seoReport.form = seoReportForm
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::report
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:104
 * @route '/site-intel/report'
 */
export const report = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(options),
    method: 'get',
})

report.definition = {
    methods: ["get","head"],
    url: '/site-intel/report',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::report
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:104
 * @route '/site-intel/report'
 */
report.url = (options?: RouteQueryOptions) => {
    return report.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::report
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:104
 * @route '/site-intel/report'
 */
report.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::report
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:104
 * @route '/site-intel/report'
 */
report.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: report.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::report
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:104
 * @route '/site-intel/report'
 */
    const reportForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: report.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::report
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:104
 * @route '/site-intel/report'
 */
        reportForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: report.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::report
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:104
 * @route '/site-intel/report'
 */
        reportForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: report.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    report.form = reportForm
const SiteIntelController = { siteHealth, domainLite, analytics, seoAudit, seoReport, report }

export default SiteIntelController