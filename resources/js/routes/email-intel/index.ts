import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::lookup
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:19
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
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:19
 * @route '/email-intel/lookup'
 */
lookup.url = (options?: RouteQueryOptions) => {
    return lookup.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::lookup
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:19
 * @route '/email-intel/lookup'
 */
lookup.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: lookup.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::lookup
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:19
 * @route '/email-intel/lookup'
 */
lookup.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: lookup.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::lookup
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:19
 * @route '/email-intel/lookup'
 */
    const lookupForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: lookup.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::lookup
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:19
 * @route '/email-intel/lookup'
 */
        lookupForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: lookup.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::lookup
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:19
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
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::report
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:28
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
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:28
 * @route '/email-intel/report'
 */
report.url = (options?: RouteQueryOptions) => {
    return report.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::report
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:28
 * @route '/email-intel/report'
 */
report.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::report
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:28
 * @route '/email-intel/report'
 */
report.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: report.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::report
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:28
 * @route '/email-intel/report'
 */
    const reportForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: report.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::report
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:28
 * @route '/email-intel/report'
 */
        reportForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: report.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\EmailIntel\EmailIntelController::report
 * @see app/Http/Controllers/EmailIntel/EmailIntelController.php:28
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
report: Object.assign(report, report),
}

export default emailIntel