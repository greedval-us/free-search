import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::context
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:33
 * @route '/mastodon/statuses/{statusId}/context'
 */
export const context = (args: { statusId: string | number } | [statusId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: context.url(args, options),
    method: 'get',
})

context.definition = {
    methods: ["get","head"],
    url: '/mastodon/statuses/{statusId}/context',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::context
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:33
 * @route '/mastodon/statuses/{statusId}/context'
 */
context.url = (args: { statusId: string | number } | [statusId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { statusId: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    statusId: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        statusId: args.statusId,
                }

    return context.definition.url
            .replace('{statusId}', parsedArgs.statusId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::context
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:33
 * @route '/mastodon/statuses/{statusId}/context'
 */
context.get = (args: { statusId: string | number } | [statusId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: context.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::context
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:33
 * @route '/mastodon/statuses/{statusId}/context'
 */
context.head = (args: { statusId: string | number } | [statusId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: context.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::context
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:33
 * @route '/mastodon/statuses/{statusId}/context'
 */
    const contextForm = (args: { statusId: string | number } | [statusId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: context.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::context
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:33
 * @route '/mastodon/statuses/{statusId}/context'
 */
        contextForm.get = (args: { statusId: string | number } | [statusId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: context.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::context
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:33
 * @route '/mastodon/statuses/{statusId}/context'
 */
        contextForm.head = (args: { statusId: string | number } | [statusId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: context.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    context.form = contextForm
const statuses = {
    context: Object.assign(context, context),
}

export default statuses