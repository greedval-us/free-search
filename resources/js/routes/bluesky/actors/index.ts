import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::feed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:66
 * @route '/bluesky/actors/feed'
 */
export const feed = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: feed.url(options),
    method: 'get',
})

feed.definition = {
    methods: ["get","head"],
    url: '/bluesky/actors/feed',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::feed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:66
 * @route '/bluesky/actors/feed'
 */
feed.url = (options?: RouteQueryOptions) => {
    return feed.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::feed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:66
 * @route '/bluesky/actors/feed'
 */
feed.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: feed.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::feed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:66
 * @route '/bluesky/actors/feed'
 */
feed.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: feed.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::feed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:66
 * @route '/bluesky/actors/feed'
 */
    const feedForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: feed.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::feed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:66
 * @route '/bluesky/actors/feed'
 */
        feedForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: feed.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::feed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:66
 * @route '/bluesky/actors/feed'
 */
        feedForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: feed.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    feed.form = feedForm
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::followers
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:77
 * @route '/bluesky/actors/followers'
 */
export const followers = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: followers.url(options),
    method: 'get',
})

followers.definition = {
    methods: ["get","head"],
    url: '/bluesky/actors/followers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::followers
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:77
 * @route '/bluesky/actors/followers'
 */
followers.url = (options?: RouteQueryOptions) => {
    return followers.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::followers
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:77
 * @route '/bluesky/actors/followers'
 */
followers.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: followers.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::followers
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:77
 * @route '/bluesky/actors/followers'
 */
followers.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: followers.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::followers
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:77
 * @route '/bluesky/actors/followers'
 */
    const followersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: followers.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::followers
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:77
 * @route '/bluesky/actors/followers'
 */
        followersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: followers.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::followers
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:77
 * @route '/bluesky/actors/followers'
 */
        followersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: followers.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    followers.form = followersForm
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::follows
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:88
 * @route '/bluesky/actors/follows'
 */
export const follows = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: follows.url(options),
    method: 'get',
})

follows.definition = {
    methods: ["get","head"],
    url: '/bluesky/actors/follows',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::follows
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:88
 * @route '/bluesky/actors/follows'
 */
follows.url = (options?: RouteQueryOptions) => {
    return follows.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::follows
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:88
 * @route '/bluesky/actors/follows'
 */
follows.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: follows.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::follows
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:88
 * @route '/bluesky/actors/follows'
 */
follows.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: follows.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::follows
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:88
 * @route '/bluesky/actors/follows'
 */
    const followsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: follows.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::follows
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:88
 * @route '/bluesky/actors/follows'
 */
        followsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: follows.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::follows
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:88
 * @route '/bluesky/actors/follows'
 */
        followsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: follows.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    follows.form = followsForm
const actors = {
    feed: Object.assign(feed, feed),
followers: Object.assign(followers, followers),
follows: Object.assign(follows, follows),
}

export default actors