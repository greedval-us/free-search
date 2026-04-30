import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Dashboard\SavedQueryController::store
 * @see app/Http/Controllers/Dashboard/SavedQueryController.php:20
 * @route '/dashboard/saved-queries'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/dashboard/saved-queries',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Dashboard\SavedQueryController::store
 * @see app/Http/Controllers/Dashboard/SavedQueryController.php:20
 * @route '/dashboard/saved-queries'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Dashboard\SavedQueryController::store
 * @see app/Http/Controllers/Dashboard/SavedQueryController.php:20
 * @route '/dashboard/saved-queries'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Dashboard\SavedQueryController::store
 * @see app/Http/Controllers/Dashboard/SavedQueryController.php:20
 * @route '/dashboard/saved-queries'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Dashboard\SavedQueryController::store
 * @see app/Http/Controllers/Dashboard/SavedQueryController.php:20
 * @route '/dashboard/saved-queries'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\Dashboard\SavedQueryController::destroy
 * @see app/Http/Controllers/Dashboard/SavedQueryController.php:29
 * @route '/dashboard/saved-queries/{savedQuery}'
 */
export const destroy = (args: { savedQuery: number | { id: number } } | [savedQuery: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/dashboard/saved-queries/{savedQuery}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Dashboard\SavedQueryController::destroy
 * @see app/Http/Controllers/Dashboard/SavedQueryController.php:29
 * @route '/dashboard/saved-queries/{savedQuery}'
 */
destroy.url = (args: { savedQuery: number | { id: number } } | [savedQuery: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { savedQuery: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { savedQuery: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    savedQuery: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        savedQuery: typeof args.savedQuery === 'object'
                ? args.savedQuery.id
                : args.savedQuery,
                }

    return destroy.definition.url
            .replace('{savedQuery}', parsedArgs.savedQuery.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Dashboard\SavedQueryController::destroy
 * @see app/Http/Controllers/Dashboard/SavedQueryController.php:29
 * @route '/dashboard/saved-queries/{savedQuery}'
 */
destroy.delete = (args: { savedQuery: number | { id: number } } | [savedQuery: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Dashboard\SavedQueryController::destroy
 * @see app/Http/Controllers/Dashboard/SavedQueryController.php:29
 * @route '/dashboard/saved-queries/{savedQuery}'
 */
    const destroyForm = (args: { savedQuery: number | { id: number } } | [savedQuery: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroy.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Dashboard\SavedQueryController::destroy
 * @see app/Http/Controllers/Dashboard/SavedQueryController.php:29
 * @route '/dashboard/saved-queries/{savedQuery}'
 */
        destroyForm.delete = (args: { savedQuery: number | { id: number } } | [savedQuery: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroy.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroy.form = destroyForm
const savedQueries = {
    store: Object.assign(store, store),
destroy: Object.assign(destroy, destroy),
}

export default savedQueries