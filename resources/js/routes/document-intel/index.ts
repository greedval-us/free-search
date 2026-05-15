import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\DocumentIntel\DocumentIntelController::lookup
 * @see app/Http/Controllers/DocumentIntel/DocumentIntelController.php:17
 * @route '/document-intel/lookup'
 */
export const lookup = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: lookup.url(options),
    method: 'get',
})

lookup.definition = {
    methods: ["get","head"],
    url: '/document-intel/lookup',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DocumentIntel\DocumentIntelController::lookup
 * @see app/Http/Controllers/DocumentIntel/DocumentIntelController.php:17
 * @route '/document-intel/lookup'
 */
lookup.url = (options?: RouteQueryOptions) => {
    return lookup.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DocumentIntel\DocumentIntelController::lookup
 * @see app/Http/Controllers/DocumentIntel/DocumentIntelController.php:17
 * @route '/document-intel/lookup'
 */
lookup.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: lookup.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DocumentIntel\DocumentIntelController::lookup
 * @see app/Http/Controllers/DocumentIntel/DocumentIntelController.php:17
 * @route '/document-intel/lookup'
 */
lookup.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: lookup.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\DocumentIntel\DocumentIntelController::lookup
 * @see app/Http/Controllers/DocumentIntel/DocumentIntelController.php:17
 * @route '/document-intel/lookup'
 */
    const lookupForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: lookup.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\DocumentIntel\DocumentIntelController::lookup
 * @see app/Http/Controllers/DocumentIntel/DocumentIntelController.php:17
 * @route '/document-intel/lookup'
 */
        lookupForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: lookup.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\DocumentIntel\DocumentIntelController::lookup
 * @see app/Http/Controllers/DocumentIntel/DocumentIntelController.php:17
 * @route '/document-intel/lookup'
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
const documentIntel = {
    lookup: Object.assign(lookup, lookup),
}

export default documentIntel