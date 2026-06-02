import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::search
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:23
 * @route '/mastodon/search'
 */
export const search = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})

search.definition = {
    methods: ["get","head"],
    url: '/mastodon/search',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::search
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:23
 * @route '/mastodon/search'
 */
search.url = (options?: RouteQueryOptions) => {
    return search.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::search
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:23
 * @route '/mastodon/search'
 */
search.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::search
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:23
 * @route '/mastodon/search'
 */
search.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: search.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::search
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:23
 * @route '/mastodon/search'
 */
    const searchForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: search.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::search
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:23
 * @route '/mastodon/search'
 */
        searchForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: search.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::search
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:23
 * @route '/mastodon/search'
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
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::context
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:32
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
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:32
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
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:32
 * @route '/mastodon/statuses/{statusId}/context'
 */
context.get = (args: { statusId: string | number } | [statusId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: context.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::context
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:32
 * @route '/mastodon/statuses/{statusId}/context'
 */
context.head = (args: { statusId: string | number } | [statusId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: context.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::context
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:32
 * @route '/mastodon/statuses/{statusId}/context'
 */
    const contextForm = (args: { statusId: string | number } | [statusId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: context.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::context
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:32
 * @route '/mastodon/statuses/{statusId}/context'
 */
        contextForm.get = (args: { statusId: string | number } | [statusId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: context.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::context
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:32
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
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountStatuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:41
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
export const accountStatuses = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: accountStatuses.url(args, options),
    method: 'get',
})

accountStatuses.definition = {
    methods: ["get","head"],
    url: '/mastodon/accounts/{accountId}/statuses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountStatuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:41
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
accountStatuses.url = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return accountStatuses.definition.url
            .replace('{accountId}', parsedArgs.accountId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountStatuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:41
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
accountStatuses.get = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: accountStatuses.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountStatuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:41
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
accountStatuses.head = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: accountStatuses.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountStatuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:41
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
    const accountStatusesForm = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: accountStatuses.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountStatuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:41
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
        accountStatusesForm.get = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: accountStatuses.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountStatuses
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:41
 * @route '/mastodon/accounts/{accountId}/statuses'
 */
        accountStatusesForm.head = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: accountStatuses.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    accountStatuses.form = accountStatusesForm
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountFollowers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:50
 * @route '/mastodon/accounts/{accountId}/followers'
 */
export const accountFollowers = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: accountFollowers.url(args, options),
    method: 'get',
})

accountFollowers.definition = {
    methods: ["get","head"],
    url: '/mastodon/accounts/{accountId}/followers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountFollowers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:50
 * @route '/mastodon/accounts/{accountId}/followers'
 */
accountFollowers.url = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return accountFollowers.definition.url
            .replace('{accountId}', parsedArgs.accountId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountFollowers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:50
 * @route '/mastodon/accounts/{accountId}/followers'
 */
accountFollowers.get = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: accountFollowers.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountFollowers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:50
 * @route '/mastodon/accounts/{accountId}/followers'
 */
accountFollowers.head = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: accountFollowers.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountFollowers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:50
 * @route '/mastodon/accounts/{accountId}/followers'
 */
    const accountFollowersForm = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: accountFollowers.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountFollowers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:50
 * @route '/mastodon/accounts/{accountId}/followers'
 */
        accountFollowersForm.get = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: accountFollowers.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Mastodon\MastodonSearchController::accountFollowers
 * @see app/Http/Controllers/Mastodon/MastodonSearchController.php:50
 * @route '/mastodon/accounts/{accountId}/followers'
 */
        accountFollowersForm.head = (args: { accountId: string | number } | [accountId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: accountFollowers.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    accountFollowers.form = accountFollowersForm
const MastodonSearchController = { search, context, accountStatuses, accountFollowers }

export default MastodonSearchController