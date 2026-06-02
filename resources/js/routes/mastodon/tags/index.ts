import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:60
 * @route '/mastodon/tags/{tagName}/statuses'
 */
export const statuses = (args: { tagName: string | number } | [tagName: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statuses.url(args, options),
    method: 'get',
})

statuses.definition = {
    methods: ["get","head"],
    url: '/mastodon/tags/{tagName}/statuses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:60
 * @route '/mastodon/tags/{tagName}/statuses'
 */
statuses.url = (args: { tagName: string | number } | [tagName: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tagName: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    tagName: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        tagName: args.tagName,
                }

    return statuses.definition.url
            .replace('{tagName}', parsedArgs.tagName.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:60
 * @route '/mastodon/tags/{tagName}/statuses'
 */
statuses.get = (args: { tagName: string | number } | [tagName: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statuses.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:60
 * @route '/mastodon/tags/{tagName}/statuses'
 */
statuses.head = (args: { tagName: string | number } | [tagName: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statuses.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:60
 * @route '/mastodon/tags/{tagName}/statuses'
 */
    const statusesForm = (args: { tagName: string | number } | [tagName: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: statuses.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:60
 * @route '/mastodon/tags/{tagName}/statuses'
 */
        statusesForm.get = (args: { tagName: string | number } | [tagName: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: statuses.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:60
 * @route '/mastodon/tags/{tagName}/statuses'
 */
        statusesForm.head = (args: { tagName: string | number } | [tagName: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: statuses.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    statuses.form = statusesForm
const tags = {
    statuses: Object.assign(statuses, statuses),
}

export default tags