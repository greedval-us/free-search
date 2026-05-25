import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::summary
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:20
 * @route '/telegram/analytics/summary'
 */
export const summary = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(options),
    method: 'get',
})

summary.definition = {
    methods: ["get","head"],
    url: '/telegram/analytics/summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::summary
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:20
 * @route '/telegram/analytics/summary'
 */
summary.url = (options?: RouteQueryOptions) => {
    return summary.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::summary
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:20
 * @route '/telegram/analytics/summary'
 */
summary.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::summary
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:20
 * @route '/telegram/analytics/summary'
 */
summary.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: summary.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::summary
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:20
 * @route '/telegram/analytics/summary'
 */
    const summaryForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: summary.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::summary
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:20
 * @route '/telegram/analytics/summary'
 */
        summaryForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: summary.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::summary
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:20
 * @route '/telegram/analytics/summary'
 */
        summaryForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: summary.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    summary.form = summaryForm
/**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::report
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:34
 * @route '/telegram/analytics/report'
 */
export const report = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(options),
    method: 'get',
})

report.definition = {
    methods: ["get","head"],
    url: '/telegram/analytics/report',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::report
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:34
 * @route '/telegram/analytics/report'
 */
report.url = (options?: RouteQueryOptions) => {
    return report.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::report
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:34
 * @route '/telegram/analytics/report'
 */
report.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::report
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:34
 * @route '/telegram/analytics/report'
 */
report.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: report.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::report
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:34
 * @route '/telegram/analytics/report'
 */
    const reportForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: report.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::report
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:34
 * @route '/telegram/analytics/report'
 */
        reportForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: report.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Telegram\TelegramAnalyticsController::report
 * @see app/Http/Controllers/Telegram/TelegramAnalyticsController.php:34
 * @route '/telegram/analytics/report'
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
const TelegramAnalyticsController = { summary, report }

export default TelegramAnalyticsController