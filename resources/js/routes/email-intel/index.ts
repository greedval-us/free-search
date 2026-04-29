import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::lookup
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:25
 * @route '/email-intel/lookup'
 */
export const lookup = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: lookup.url(options),
    method: 'get',
})

lookup.definition = {
    methods: ["get","head"],
    url: '/email-intel/lookup',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::lookup
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:25
 * @route '/email-intel/lookup'
 */
lookup.url = (options?: RouteQueryOptions) => {
    return lookup.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::lookup
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:25
 * @route '/email-intel/lookup'
 */
lookup.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: lookup.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::lookup
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:25
 * @route '/email-intel/lookup'
 */
lookup.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: lookup.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::lookup
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:25
 * @route '/email-intel/lookup'
 */
    const lookupForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: lookup.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::lookup
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:25
 * @route '/email-intel/lookup'
 */
        lookupForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: lookup.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::lookup
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:25
 * @route '/email-intel/lookup'
 */
        lookupForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: lookup.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    lookup.form = lookupForm
/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::bulk
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:49
 * @route '/email-intel/bulk'
 */
export const bulk = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: bulk.url(options),
    method: 'get',
})

bulk.definition = {
    methods: ["get","head"],
    url: '/email-intel/bulk',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::bulk
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:49
 * @route '/email-intel/bulk'
 */
bulk.url = (options?: RouteQueryOptions) => {
    return bulk.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::bulk
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:49
 * @route '/email-intel/bulk'
 */
bulk.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: bulk.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::bulk
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:49
 * @route '/email-intel/bulk'
 */
bulk.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: bulk.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::bulk
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:49
 * @route '/email-intel/bulk'
 */
    const bulkForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: bulk.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::bulk
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:49
 * @route '/email-intel/bulk'
 */
        bulkForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: bulk.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::bulk
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:49
 * @route '/email-intel/bulk'
 */
        bulkForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: bulk.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    bulk.form = bulkForm
/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::domainPosture
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:58
 * @route '/email-intel/domain-posture'
 */
export const domainPosture = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: domainPosture.url(options),
    method: 'get',
})

domainPosture.definition = {
    methods: ["get","head"],
    url: '/email-intel/domain-posture',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::domainPosture
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:58
 * @route '/email-intel/domain-posture'
 */
domainPosture.url = (options?: RouteQueryOptions) => {
    return domainPosture.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::domainPosture
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:58
 * @route '/email-intel/domain-posture'
 */
domainPosture.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: domainPosture.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::domainPosture
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:58
 * @route '/email-intel/domain-posture'
 */
domainPosture.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: domainPosture.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::domainPosture
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:58
 * @route '/email-intel/domain-posture'
 */
    const domainPostureForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: domainPosture.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::domainPosture
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:58
 * @route '/email-intel/domain-posture'
 */
        domainPostureForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: domainPosture.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::domainPosture
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:58
 * @route '/email-intel/domain-posture'
 */
        domainPostureForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: domainPosture.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    domainPosture.form = domainPostureForm
/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::report
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:34
 * @route '/email-intel/report'
 */
export const report = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(options),
    method: 'get',
})

report.definition = {
    methods: ["get","head"],
    url: '/email-intel/report',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::report
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:34
 * @route '/email-intel/report'
 */
report.url = (options?: RouteQueryOptions) => {
    return report.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::report
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:34
 * @route '/email-intel/report'
 */
report.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::report
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:34
 * @route '/email-intel/report'
 */
report.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: report.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::report
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:34
 * @route '/email-intel/report'
 */
    const reportForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: report.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::report
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:34
 * @route '/email-intel/report'
 */
        reportForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: report.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::report
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:34
 * @route '/email-intel/report'
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
const emailIntel = {
    lookup: Object.assign(lookup, lookup),
bulk: Object.assign(bulk, bulk),
domainPosture: Object.assign(domainPosture, domainPosture),
report: Object.assign(report, report),
}

export default emailIntel