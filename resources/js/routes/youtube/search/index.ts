import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\YouTube\YouTubeSearchController::videos
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:14
 * @route '/youtube/search/videos'
 */
export const videos = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: videos.url(options),
    method: 'get',
})

videos.definition = {
    methods: ["get","head"],
    url: '/youtube/search/videos',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\YouTube\YouTubeSearchController::videos
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:14
 * @route '/youtube/search/videos'
 */
videos.url = (options?: RouteQueryOptions) => {
    return videos.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\YouTube\YouTubeSearchController::videos
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:14
 * @route '/youtube/search/videos'
 */
videos.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: videos.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\YouTube\YouTubeSearchController::videos
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:14
 * @route '/youtube/search/videos'
 */
videos.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: videos.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\YouTube\YouTubeSearchController::videos
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:14
 * @route '/youtube/search/videos'
 */
    const videosForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: videos.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\YouTube\YouTubeSearchController::videos
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:14
 * @route '/youtube/search/videos'
 */
        videosForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: videos.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\YouTube\YouTubeSearchController::videos
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:14
 * @route '/youtube/search/videos'
 */
        videosForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: videos.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    videos.form = videosForm
/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::commentsPreview
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:35
 * @route '/youtube/search/comments-preview'
 */
export const commentsPreview = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: commentsPreview.url(options),
    method: 'get',
})

commentsPreview.definition = {
    methods: ["get","head"],
    url: '/youtube/search/comments-preview',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::commentsPreview
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:35
 * @route '/youtube/search/comments-preview'
 */
commentsPreview.url = (options?: RouteQueryOptions) => {
    return commentsPreview.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::commentsPreview
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:35
 * @route '/youtube/search/comments-preview'
 */
commentsPreview.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: commentsPreview.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::commentsPreview
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:35
 * @route '/youtube/search/comments-preview'
 */
commentsPreview.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: commentsPreview.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::commentsPreview
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:35
 * @route '/youtube/search/comments-preview'
 */
    const commentsPreviewForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: commentsPreview.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::commentsPreview
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:35
 * @route '/youtube/search/comments-preview'
 */
        commentsPreviewForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: commentsPreview.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::commentsPreview
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:35
 * @route '/youtube/search/comments-preview'
 */
        commentsPreviewForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: commentsPreview.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    commentsPreview.form = commentsPreviewForm
const search = {
    videos: Object.assign(videos, videos),
commentsPreview: Object.assign(commentsPreview, commentsPreview),
}

export default search