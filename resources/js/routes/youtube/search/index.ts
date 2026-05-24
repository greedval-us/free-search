import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\YouTube\YouTubeSearchController::videos
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:18
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
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:18
 * @route '/youtube/search/videos'
 */
videos.url = (options?: RouteQueryOptions) => {
    return videos.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\YouTube\YouTubeSearchController::videos
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:18
 * @route '/youtube/search/videos'
 */
videos.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: videos.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\YouTube\YouTubeSearchController::videos
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:18
 * @route '/youtube/search/videos'
 */
videos.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: videos.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\YouTube\YouTubeSearchController::videos
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:18
 * @route '/youtube/search/videos'
 */
    const videosForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: videos.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\YouTube\YouTubeSearchController::videos
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:18
 * @route '/youtube/search/videos'
 */
        videosForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: videos.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\YouTube\YouTubeSearchController::videos
 * @see app/Http/Controllers/YouTube/YouTubeSearchController.php:18
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
const search = {
    videos: Object.assign(videos, videos),
}

export default search