import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Gdelt\GdeltSearchController::articles
 * @see app/Http/Controllers/Gdelt/GdeltSearchController.php:16
 * @route '/gdelt/search/articles'
 */
export const articles = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: articles.url(options),
    method: 'get',
})

articles.definition = {
    methods: ["get","head"],
    url: '/gdelt/search/articles',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Gdelt\GdeltSearchController::articles
 * @see app/Http/Controllers/Gdelt/GdeltSearchController.php:16
 * @route '/gdelt/search/articles'
 */
articles.url = (options?: RouteQueryOptions) => {
    return articles.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Gdelt\GdeltSearchController::articles
 * @see app/Http/Controllers/Gdelt/GdeltSearchController.php:16
 * @route '/gdelt/search/articles'
 */
articles.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: articles.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Gdelt\GdeltSearchController::articles
 * @see app/Http/Controllers/Gdelt/GdeltSearchController.php:16
 * @route '/gdelt/search/articles'
 */
articles.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: articles.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Gdelt\GdeltSearchController::articles
 * @see app/Http/Controllers/Gdelt/GdeltSearchController.php:16
 * @route '/gdelt/search/articles'
 */
    const articlesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: articles.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Gdelt\GdeltSearchController::articles
 * @see app/Http/Controllers/Gdelt/GdeltSearchController.php:16
 * @route '/gdelt/search/articles'
 */
        articlesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: articles.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Gdelt\GdeltSearchController::articles
 * @see app/Http/Controllers/Gdelt/GdeltSearchController.php:16
 * @route '/gdelt/search/articles'
 */
        articlesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: articles.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    articles.form = articlesForm
const search = {
    articles: Object.assign(articles, articles),
}

export default search