import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../../../../wayfinder';
/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::options
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:22
 * @route '/news-media-intel/options'
 */
export const options = (
    routeOptions?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: options.url(routeOptions),
    method: 'get',
});

options.definition = {
    methods: ['get', 'head'],
    url: '/news-media-intel/options',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::options
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:22
 * @route '/news-media-intel/options'
 */
options.url = (routeOptions?: RouteQueryOptions) => {
    return options.definition.url + queryParams(routeOptions);
};

/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::options
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:22
 * @route '/news-media-intel/options'
 */
options.get = (routeOptions?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: options.url(routeOptions),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::options
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:22
 * @route '/news-media-intel/options'
 */
options.head = (routeOptions?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: options.url(routeOptions),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::options
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:22
 * @route '/news-media-intel/options'
 */
const optionsForm = (
    routeOptions?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: options.url(routeOptions),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::options
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:22
 * @route '/news-media-intel/options'
 */
optionsForm.get = (
    routeOptions?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: options.url(routeOptions),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::options
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:22
 * @route '/news-media-intel/options'
 */
optionsForm.head = (
    routeOptions?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: options.url({
        [routeOptions?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(routeOptions?.query ?? routeOptions?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

options.form = optionsForm;
/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::analytics
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:34
 * @route '/news-media-intel/analytics'
 */
export const analytics = (
    options?: RouteQueryOptions
): RouteDefinition<'post'> => ({
    url: analytics.url(options),
    method: 'post',
});

analytics.definition = {
    methods: ['post'],
    url: '/news-media-intel/analytics',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::analytics
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:34
 * @route '/news-media-intel/analytics'
 */
analytics.url = (options?: RouteQueryOptions) => {
    return analytics.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::analytics
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:34
 * @route '/news-media-intel/analytics'
 */
analytics.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: analytics.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::analytics
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:34
 * @route '/news-media-intel/analytics'
 */
const analyticsForm = (
    options?: RouteQueryOptions
): RouteFormDefinition<'post'> => ({
    action: analytics.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::analytics
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:34
 * @route '/news-media-intel/analytics'
 */
analyticsForm.post = (
    options?: RouteQueryOptions
): RouteFormDefinition<'post'> => ({
    action: analytics.url(options),
    method: 'post',
});

analytics.form = analyticsForm;
/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::report
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:46
 * @route '/news-media-intel/report/{reportId}'
 */
export const report = (
    args:
        | { reportId: string | number }
        | [reportId: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: report.url(args, options),
    method: 'get',
});

report.definition = {
    methods: ['get', 'head'],
    url: '/news-media-intel/report/{reportId}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::report
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:46
 * @route '/news-media-intel/report/{reportId}'
 */
report.url = (
    args:
        | { reportId: string | number }
        | [reportId: string | number]
        | string
        | number,
    options?: RouteQueryOptions
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reportId: args };
    }

    if (Array.isArray(args)) {
        args = {
            reportId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        reportId: args.reportId,
    };

    return (
        report.definition.url
            .replace('{reportId}', parsedArgs.reportId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::report
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:46
 * @route '/news-media-intel/report/{reportId}'
 */
report.get = (
    args:
        | { reportId: string | number }
        | [reportId: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: report.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::report
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:46
 * @route '/news-media-intel/report/{reportId}'
 */
report.head = (
    args:
        | { reportId: string | number }
        | [reportId: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteDefinition<'head'> => ({
    url: report.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::report
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:46
 * @route '/news-media-intel/report/{reportId}'
 */
const reportForm = (
    args:
        | { reportId: string | number }
        | [reportId: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: report.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::report
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:46
 * @route '/news-media-intel/report/{reportId}'
 */
reportForm.get = (
    args:
        | { reportId: string | number }
        | [reportId: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: report.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\NewsMediaIntel\NewsMarketingAnalyticsController::report
 * @see app/Http/Controllers/NewsMediaIntel/NewsMarketingAnalyticsController.php:46
 * @route '/news-media-intel/report/{reportId}'
 */
reportForm.head = (
    args:
        | { reportId: string | number }
        | [reportId: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: report.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

report.form = reportForm;
const NewsMarketingAnalyticsController = { options, analytics, report };

export default NewsMarketingAnalyticsController;
