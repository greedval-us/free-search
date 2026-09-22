import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PublicSiteController::home
 * @see app/Http/Controllers/PublicSiteController.php:15
 * @route '/'
 */
export const home = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

home.definition = {
    methods: ["get","head"],
    url: '/',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicSiteController::home
 * @see app/Http/Controllers/PublicSiteController.php:15
 * @route '/'
 */
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicSiteController::home
 * @see app/Http/Controllers/PublicSiteController.php:15
 * @route '/'
 */
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PublicSiteController::home
 * @see app/Http/Controllers/PublicSiteController.php:15
 * @route '/'
 */
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\PublicSiteController::home
 * @see app/Http/Controllers/PublicSiteController.php:15
 * @route '/'
 */
    const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: home.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\PublicSiteController::home
 * @see app/Http/Controllers/PublicSiteController.php:15
 * @route '/'
 */
        homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: home.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\PublicSiteController::home
 * @see app/Http/Controllers/PublicSiteController.php:15
 * @route '/'
 */
        homeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: home.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    home.form = homeForm
/**
* @see \App\Http\Controllers\PublicSiteController::index
 * @see app/Http/Controllers/PublicSiteController.php:20
 * @route '/features'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/features',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicSiteController::index
 * @see app/Http/Controllers/PublicSiteController.php:20
 * @route '/features'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicSiteController::index
 * @see app/Http/Controllers/PublicSiteController.php:20
 * @route '/features'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PublicSiteController::index
 * @see app/Http/Controllers/PublicSiteController.php:20
 * @route '/features'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\PublicSiteController::index
 * @see app/Http/Controllers/PublicSiteController.php:20
 * @route '/features'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\PublicSiteController::index
 * @see app/Http/Controllers/PublicSiteController.php:20
 * @route '/features'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\PublicSiteController::index
 * @see app/Http/Controllers/PublicSiteController.php:20
 * @route '/features'
 */
        indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    index.form = indexForm
/**
* @see \App\Http\Controllers\PublicSiteController::show
 * @see app/Http/Controllers/PublicSiteController.php:25
 * @route '/features/{feature}'
 */
export const show = (args: { feature: string | number } | [feature: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/features/{feature}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicSiteController::show
 * @see app/Http/Controllers/PublicSiteController.php:25
 * @route '/features/{feature}'
 */
show.url = (args: { feature: string | number } | [feature: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { feature: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    feature: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        feature: args.feature,
                }

    return show.definition.url
            .replace('{feature}', parsedArgs.feature.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicSiteController::show
 * @see app/Http/Controllers/PublicSiteController.php:25
 * @route '/features/{feature}'
 */
show.get = (args: { feature: string | number } | [feature: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PublicSiteController::show
 * @see app/Http/Controllers/PublicSiteController.php:25
 * @route '/features/{feature}'
 */
show.head = (args: { feature: string | number } | [feature: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\PublicSiteController::show
 * @see app/Http/Controllers/PublicSiteController.php:25
 * @route '/features/{feature}'
 */
    const showForm = (args: { feature: string | number } | [feature: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\PublicSiteController::show
 * @see app/Http/Controllers/PublicSiteController.php:25
 * @route '/features/{feature}'
 */
        showForm.get = (args: { feature: string | number } | [feature: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\PublicSiteController::show
 * @see app/Http/Controllers/PublicSiteController.php:25
 * @route '/features/{feature}'
 */
        showForm.head = (args: { feature: string | number } | [feature: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    show.form = showForm
const PublicSiteController = { home, index, show }

export default PublicSiteController