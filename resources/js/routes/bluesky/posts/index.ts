import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::likes
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:33
 * @route '/bluesky/posts/likes'
 */
export const likes = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: likes.url(options),
    method: 'get',
})

likes.definition = {
    methods: ["get","head"],
    url: '/bluesky/posts/likes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::likes
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:33
 * @route '/bluesky/posts/likes'
 */
likes.url = (options?: RouteQueryOptions) => {
    return likes.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::likes
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:33
 * @route '/bluesky/posts/likes'
 */
likes.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: likes.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::likes
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:33
 * @route '/bluesky/posts/likes'
 */
likes.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: likes.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::likes
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:33
 * @route '/bluesky/posts/likes'
 */
    const likesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: likes.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::likes
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:33
 * @route '/bluesky/posts/likes'
 */
        likesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: likes.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::likes
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:33
 * @route '/bluesky/posts/likes'
 */
        likesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: likes.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    likes.form = likesForm
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::reposts
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:44
 * @route '/bluesky/posts/reposts'
 */
export const reposts = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reposts.url(options),
    method: 'get',
})

reposts.definition = {
    methods: ["get","head"],
    url: '/bluesky/posts/reposts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::reposts
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:44
 * @route '/bluesky/posts/reposts'
 */
reposts.url = (options?: RouteQueryOptions) => {
    return reposts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::reposts
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:44
 * @route '/bluesky/posts/reposts'
 */
reposts.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reposts.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::reposts
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:44
 * @route '/bluesky/posts/reposts'
 */
reposts.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: reposts.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::reposts
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:44
 * @route '/bluesky/posts/reposts'
 */
    const repostsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: reposts.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::reposts
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:44
 * @route '/bluesky/posts/reposts'
 */
        repostsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: reposts.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::reposts
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:44
 * @route '/bluesky/posts/reposts'
 */
        repostsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: reposts.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    reposts.form = repostsForm
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::thread
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:55
 * @route '/bluesky/posts/thread'
 */
export const thread = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: thread.url(options),
    method: 'get',
})

thread.definition = {
    methods: ["get","head"],
    url: '/bluesky/posts/thread',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::thread
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:55
 * @route '/bluesky/posts/thread'
 */
thread.url = (options?: RouteQueryOptions) => {
    return thread.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::thread
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:55
 * @route '/bluesky/posts/thread'
 */
thread.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: thread.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::thread
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:55
 * @route '/bluesky/posts/thread'
 */
thread.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: thread.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::thread
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:55
 * @route '/bluesky/posts/thread'
 */
    const threadForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: thread.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::thread
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:55
 * @route '/bluesky/posts/thread'
 */
        threadForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: thread.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::thread
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:55
 * @route '/bluesky/posts/thread'
 */
        threadForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: thread.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    thread.form = threadForm
const posts = {
    likes: Object.assign(likes, likes),
reposts: Object.assign(reposts, reposts),
thread: Object.assign(thread, thread),
}

export default posts