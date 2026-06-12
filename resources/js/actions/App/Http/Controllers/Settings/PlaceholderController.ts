import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Settings\PlaceholderController::show
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/settings/placeholder',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\PlaceholderController::show
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\PlaceholderController::show
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Settings\PlaceholderController::show
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Settings\PlaceholderController::show
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
    const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Settings\PlaceholderController::show
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
        showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Settings\PlaceholderController::show
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
        showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    show.form = showForm
const PlaceholderController = { show }

export default PlaceholderController