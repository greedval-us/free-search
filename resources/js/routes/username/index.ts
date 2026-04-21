import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\Username\UsernameSearchController::search
 * @see app/Http/Controllers/Username/UsernameSearchController.php:20
 * @route '/username/search'
 */
export const search = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})

search.definition = {
    methods: ["get","head"],
    url: '/username/search',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Username\UsernameSearchController::search
 * @see app/Http/Controllers/Username/UsernameSearchController.php:20
 * @route '/username/search'
 */
search.url = (options?: RouteQueryOptions) => {
    return search.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Username\UsernameSearchController::search
 * @see app/Http/Controllers/Username/UsernameSearchController.php:20
 * @route '/username/search'
 */
search.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Username\UsernameSearchController::search
 * @see app/Http/Controllers/Username/UsernameSearchController.php:20
 * @route '/username/search'
 */
search.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: search.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Username\UsernameSearchController::search
 * @see app/Http/Controllers/Username/UsernameSearchController.php:20
 * @route '/username/search'
 */
    const searchForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: search.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Username\UsernameSearchController::search
 * @see app/Http/Controllers/Username/UsernameSearchController.php:20
 * @route '/username/search'
 */
        searchForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: search.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Username\UsernameSearchController::search
 * @see app/Http/Controllers/Username/UsernameSearchController.php:20
 * @route '/username/search'
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
* @see \App\Http\Controllers\Username\UsernameSearchController::report
 * @see app/Http/Controllers/Username/UsernameSearchController.php:30
 * @route '/username/report'
 */
export const report = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(options),
    method: 'get',
})

report.definition = {
    methods: ["get","head"],
    url: '/username/report',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Username\UsernameSearchController::report
 * @see app/Http/Controllers/Username/UsernameSearchController.php:30
 * @route '/username/report'
 */
report.url = (options?: RouteQueryOptions) => {
    return report.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Username\UsernameSearchController::report
 * @see app/Http/Controllers/Username/UsernameSearchController.php:30
 * @route '/username/report'
 */
report.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Username\UsernameSearchController::report
 * @see app/Http/Controllers/Username/UsernameSearchController.php:30
 * @route '/username/report'
 */
report.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: report.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Username\UsernameSearchController::report
 * @see app/Http/Controllers/Username/UsernameSearchController.php:30
 * @route '/username/report'
 */
    const reportForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: report.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Username\UsernameSearchController::report
 * @see app/Http/Controllers/Username/UsernameSearchController.php:30
 * @route '/username/report'
 */
        reportForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: report.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Username\UsernameSearchController::report
 * @see app/Http/Controllers/Username/UsernameSearchController.php:30
 * @route '/username/report'
 */
        reportForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: report.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    report.form = reportForm
const username = {
    search: Object.assign(search, search),
report: Object.assign(report, report),
}

export default username