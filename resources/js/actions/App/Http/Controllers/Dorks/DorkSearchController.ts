import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Dorks\DorkSearchController::search
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:21
 * @route '/dorks/search'
 */
export const search = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})

search.definition = {
    methods: ["get","head"],
    url: '/dorks/search',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Dorks\DorkSearchController::search
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:21
 * @route '/dorks/search'
 */
search.url = (options?: RouteQueryOptions) => {
    return search.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Dorks\DorkSearchController::search
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:21
 * @route '/dorks/search'
 */
search.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Dorks\DorkSearchController::search
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:21
 * @route '/dorks/search'
 */
search.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: search.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Dorks\DorkSearchController::search
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:21
 * @route '/dorks/search'
 */
    const searchForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: search.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Dorks\DorkSearchController::search
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:21
 * @route '/dorks/search'
 */
        searchForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: search.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Dorks\DorkSearchController::search
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:21
 * @route '/dorks/search'
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
* @see \App\Http\Controllers\Dorks\DorkSearchController::goals
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:34
 * @route '/dorks/goals'
 */
export const goals = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: goals.url(options),
    method: 'get',
})

goals.definition = {
    methods: ["get","head"],
    url: '/dorks/goals',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Dorks\DorkSearchController::goals
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:34
 * @route '/dorks/goals'
 */
goals.url = (options?: RouteQueryOptions) => {
    return goals.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Dorks\DorkSearchController::goals
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:34
 * @route '/dorks/goals'
 */
goals.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: goals.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Dorks\DorkSearchController::goals
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:34
 * @route '/dorks/goals'
 */
goals.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: goals.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Dorks\DorkSearchController::goals
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:34
 * @route '/dorks/goals'
 */
    const goalsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: goals.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Dorks\DorkSearchController::goals
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:34
 * @route '/dorks/goals'
 */
        goalsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: goals.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Dorks\DorkSearchController::goals
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:34
 * @route '/dorks/goals'
 */
        goalsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: goals.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    goals.form = goalsForm
/**
* @see \App\Http\Controllers\Dorks\DorkSearchController::report
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:47
 * @route '/dorks/report'
 */
export const report = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(options),
    method: 'get',
})

report.definition = {
    methods: ["get","head"],
    url: '/dorks/report',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Dorks\DorkSearchController::report
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:47
 * @route '/dorks/report'
 */
report.url = (options?: RouteQueryOptions) => {
    return report.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Dorks\DorkSearchController::report
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:47
 * @route '/dorks/report'
 */
report.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Dorks\DorkSearchController::report
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:47
 * @route '/dorks/report'
 */
report.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: report.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Dorks\DorkSearchController::report
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:47
 * @route '/dorks/report'
 */
    const reportForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: report.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Dorks\DorkSearchController::report
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:47
 * @route '/dorks/report'
 */
        reportForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: report.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Dorks\DorkSearchController::report
 * @see app/Http/Controllers/Dorks/DorkSearchController.php:47
 * @route '/dorks/report'
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
const DorkSearchController = { search, goals, report }

export default DorkSearchController