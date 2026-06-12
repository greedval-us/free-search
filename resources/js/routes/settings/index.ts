import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\Settings\PlaceholderController::placeholder
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
export const placeholder = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: placeholder.url(options),
    method: 'get',
})

placeholder.definition = {
    methods: ["get","head"],
    url: '/settings/placeholder',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\PlaceholderController::placeholder
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
placeholder.url = (options?: RouteQueryOptions) => {
    return placeholder.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\PlaceholderController::placeholder
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
placeholder.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: placeholder.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Settings\PlaceholderController::placeholder
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
placeholder.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: placeholder.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Settings\PlaceholderController::placeholder
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
    const placeholderForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: placeholder.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Settings\PlaceholderController::placeholder
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
        placeholderForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: placeholder.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Settings\PlaceholderController::placeholder
 * @see app/Http/Controllers/Settings/PlaceholderController.php:12
 * @route '/settings/placeholder'
 */
        placeholderForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: placeholder.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    placeholder.form = placeholderForm
const settings = {
    placeholder: Object.assign(placeholder, placeholder),
}

export default settings