import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::search
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:20
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
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:20
 * @route '/bluesky/search'
 */
search.url = (options?: RouteQueryOptions) => {
    return search.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::search
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:20
 * @route '/bluesky/search'
 */
search.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::search
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:20
 * @route '/bluesky/search'
 */
search.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: search.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::search
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:20
 * @route '/bluesky/search'
 */
    const searchForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: search.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::search
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:20
 * @route '/bluesky/search'
 */
        searchForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: search.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::search
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:20
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
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::likes
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:25
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
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:25
 * @route '/bluesky/posts/likes'
 */
likes.url = (options?: RouteQueryOptions) => {
    return likes.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::likes
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:25
 * @route '/bluesky/posts/likes'
 */
likes.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: likes.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::likes
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:25
 * @route '/bluesky/posts/likes'
 */
likes.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: likes.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::likes
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:25
 * @route '/bluesky/posts/likes'
 */
    const likesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: likes.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::likes
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:25
 * @route '/bluesky/posts/likes'
 */
        likesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: likes.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::likes
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:25
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
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:32
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
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:32
 * @route '/bluesky/posts/reposts'
 */
reposts.url = (options?: RouteQueryOptions) => {
    return reposts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::reposts
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:32
 * @route '/bluesky/posts/reposts'
 */
reposts.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reposts.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::reposts
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:32
 * @route '/bluesky/posts/reposts'
 */
reposts.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: reposts.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::reposts
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:32
 * @route '/bluesky/posts/reposts'
 */
    const repostsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: reposts.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::reposts
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:32
 * @route '/bluesky/posts/reposts'
 */
        repostsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: reposts.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::reposts
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:32
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
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:39
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
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:39
 * @route '/bluesky/posts/thread'
 */
thread.url = (options?: RouteQueryOptions) => {
    return thread.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::thread
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:39
 * @route '/bluesky/posts/thread'
 */
thread.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: thread.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::thread
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:39
 * @route '/bluesky/posts/thread'
 */
thread.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: thread.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::thread
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:39
 * @route '/bluesky/posts/thread'
 */
    const threadForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: thread.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::thread
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:39
 * @route '/bluesky/posts/thread'
 */
        threadForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: thread.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::thread
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:39
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
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::authorFeed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:46
 * @route '/bluesky/actors/feed'
 */
export const authorFeed = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: authorFeed.url(options),
    method: 'get',
})

authorFeed.definition = {
    methods: ["get","head"],
    url: '/bluesky/actors/feed',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::authorFeed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:46
 * @route '/bluesky/actors/feed'
 */
authorFeed.url = (options?: RouteQueryOptions) => {
    return authorFeed.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::authorFeed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:46
 * @route '/bluesky/actors/feed'
 */
authorFeed.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: authorFeed.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::authorFeed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:46
 * @route '/bluesky/actors/feed'
 */
authorFeed.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: authorFeed.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::authorFeed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:46
 * @route '/bluesky/actors/feed'
 */
    const authorFeedForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: authorFeed.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::authorFeed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:46
 * @route '/bluesky/actors/feed'
 */
        authorFeedForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: authorFeed.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::authorFeed
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:46
 * @route '/bluesky/actors/feed'
 */
        authorFeedForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: authorFeed.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    authorFeed.form = authorFeedForm
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::followers
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:53
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
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:53
 * @route '/bluesky/actors/followers'
 */
followers.url = (options?: RouteQueryOptions) => {
    return followers.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::followers
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:53
 * @route '/bluesky/actors/followers'
 */
followers.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: followers.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::followers
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:53
 * @route '/bluesky/actors/followers'
 */
followers.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: followers.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::followers
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:53
 * @route '/bluesky/actors/followers'
 */
    const followersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: followers.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::followers
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:53
 * @route '/bluesky/actors/followers'
 */
        followersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: followers.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::followers
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:53
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
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:60
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
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:60
 * @route '/bluesky/actors/follows'
 */
follows.url = (options?: RouteQueryOptions) => {
    return follows.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::follows
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:60
 * @route '/bluesky/actors/follows'
 */
follows.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: follows.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::follows
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:60
 * @route '/bluesky/actors/follows'
 */
follows.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: follows.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::follows
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:60
 * @route '/bluesky/actors/follows'
 */
    const followsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: follows.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::follows
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:60
 * @route '/bluesky/actors/follows'
 */
        followsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: follows.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Bluesky\BlueskySearchController::follows
 * @see app/Http/Controllers/Bluesky/BlueskySearchController.php:60
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
const BlueskySearchController = { search, likes, reposts, thread, authorFeed, followers, follows }

export default BlueskySearchController