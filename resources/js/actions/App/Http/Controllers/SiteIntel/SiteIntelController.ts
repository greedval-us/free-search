import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:20
 * @route '/localhost/site-intel/site-health'
 */
export const siteHealth = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: siteHealth.url(options),
    method: 'get',
})

siteHealth.definition = {
    methods: ["get","head"],
    url: '/localhost/site-intel/site-health',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:20
 * @route '/localhost/site-intel/site-health'
 */
siteHealth.url = (options?: RouteQueryOptions) => {
    return siteHealth.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:20
 * @route '/localhost/site-intel/site-health'
 */
siteHealth.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: siteHealth.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:20
 * @route '/localhost/site-intel/site-health'
 */
siteHealth.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: siteHealth.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:20
 * @route '/localhost/site-intel/site-health'
 */
    const siteHealthForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: siteHealth.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:20
 * @route '/localhost/site-intel/site-health'
 */
        siteHealthForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: siteHealth.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::siteHealth
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:20
 * @route '/localhost/site-intel/site-health'
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
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:38
 * @route '/localhost/site-intel/domain-lite'
 */
export const domainLite = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: domainLite.url(options),
    method: 'get',
})

domainLite.definition = {
    methods: ["get","head"],
    url: '/localhost/site-intel/domain-lite',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::domainLite
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:38
 * @route '/localhost/site-intel/domain-lite'
 */
domainLite.url = (options?: RouteQueryOptions) => {
    return domainLite.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::domainLite
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:38
 * @route '/localhost/site-intel/domain-lite'
 */
domainLite.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: domainLite.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::domainLite
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:38
 * @route '/localhost/site-intel/domain-lite'
 */
domainLite.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: domainLite.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::domainLite
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:38
 * @route '/localhost/site-intel/domain-lite'
 */
    const domainLiteForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: domainLite.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::domainLite
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:38
 * @route '/localhost/site-intel/domain-lite'
 */
        domainLiteForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: domainLite.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\SiteIntel\SiteIntelController::domainLite
 * @see app/Http/Controllers/SiteIntel/SiteIntelController.php:38
 * @route '/localhost/site-intel/domain-lite'
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
const SiteIntelController = { siteHealth, domainLite }

export default SiteIntelController