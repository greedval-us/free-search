import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::search
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:21
 * @route '/bluesky/search'
 */
export const search = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})

search.definition = {
    methods: ["get","head"],
    url: '/bluesky/search',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::search
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:21
 * @route '/bluesky/search'
 */
search.url = (options?: RouteQueryOptions) => {
    return search.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::search
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:21
 * @route '/bluesky/search'
 */
search.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::search
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:21
 * @route '/bluesky/search'
 */
search.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: search.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::search
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:21
 * @route '/bluesky/search'
 */
    const searchForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: search.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::search
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:21
 * @route '/bluesky/search'
 */
        searchForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: search.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::search
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:21
 * @route '/bluesky/search'
 */
        searchForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: search.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    search.form = searchForm
const BlueskySearchController = { search }

export default BlueskySearchController