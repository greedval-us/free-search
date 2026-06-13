import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:30
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
export const statuses = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statuses.url(args, options),
    method: 'get',
})

statuses.definition = {
    methods: ["get","head"],
    url: '/mastodon/accounts/{accountId}/statuses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:30
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
statuses.url = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { accountId: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    accountId: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        accountId: args.accountId,
                }

    return statuses.definition.url
            .replace('{accountId}', parsedArgs.accountId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:30
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
statuses.get = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statuses.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:30
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
statuses.head = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statuses.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:30
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
    const statusesForm = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: statuses.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:30
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
        statusesForm.get = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: statuses.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::statuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:30
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
        statusesForm.head = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: statuses.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    statuses.form = statusesForm
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::followers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:35
 * @route '/mastodon/accounts/{accountId}/followers'
 */
export const followers = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: followers.url(args, options),
    method: 'get',
})

followers.definition = {
    methods: ["get","head"],
    url: '/mastodon/accounts/{accountId}/followers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::followers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:35
 * @route '/mastodon/accounts/{accountId}/followers'
 */
followers.url = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { accountId: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    accountId: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        accountId: args.accountId,
                }

    return followers.definition.url
            .replace('{accountId}', parsedArgs.accountId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::followers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:35
 * @route '/mastodon/accounts/{accountId}/followers'
 */
followers.get = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: followers.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::followers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:35
 * @route '/mastodon/accounts/{accountId}/followers'
 */
followers.head = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: followers.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::followers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:35
 * @route '/mastodon/accounts/{accountId}/followers'
 */
    const followersForm = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: followers.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::followers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:35
 * @route '/mastodon/accounts/{accountId}/followers'
 */
        followersForm.get = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: followers.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::followers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:35
 * @route '/mastodon/accounts/{accountId}/followers'
 */
        followersForm.head = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: followers.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    followers.form = followersForm
const accounts = {
    statuses: Object.assign(statuses, statuses),
followers: Object.assign(followers, followers),
}

export default accounts