import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../../../../wayfinder';
/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::index
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:22
 * @route '/site-intel/reports'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/site-intel/reports',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::index
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:22
 * @route '/site-intel/reports'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::index
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:22
 * @route '/site-intel/reports'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::index
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:22
 * @route '/site-intel/reports'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::index
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:22
 * @route '/site-intel/reports'
 */
const indexForm = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::index
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:22
 * @route '/site-intel/reports'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::index
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:22
 * @route '/site-intel/reports'
 */
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

index.form = indexForm;
/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::store
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:44
 * @route '/site-intel/reports/schedules'
 */
export const store = (
    options?: RouteQueryOptions
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/site-intel/reports/schedules',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::store
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:44
 * @route '/site-intel/reports/schedules'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::store
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:44
 * @route '/site-intel/reports/schedules'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::store
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:44
 * @route '/site-intel/reports/schedules'
 */
const storeForm = (
    options?: RouteQueryOptions
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::store
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:44
 * @route '/site-intel/reports/schedules'
 */
storeForm.post = (
    options?: RouteQueryOptions
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::change
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:51
 * @route '/site-intel/reports/schedules/{schedule}'
 */
export const change = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteDefinition<'patch'> => ({
    url: change.url(args, options),
    method: 'patch',
});

change.definition = {
    methods: ['patch'],
    url: '/site-intel/reports/schedules/{schedule}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::change
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:51
 * @route '/site-intel/reports/schedules/{schedule}'
 */
change.url = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { schedule: args };
    }

    if (Array.isArray(args)) {
        args = {
            schedule: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        schedule: args.schedule,
    };

    return (
        change.definition.url
            .replace('{schedule}', parsedArgs.schedule.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::change
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:51
 * @route '/site-intel/reports/schedules/{schedule}'
 */
change.patch = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteDefinition<'patch'> => ({
    url: change.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::change
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:51
 * @route '/site-intel/reports/schedules/{schedule}'
 */
const changeForm = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteFormDefinition<'post'> => ({
    action: change.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::change
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:51
 * @route '/site-intel/reports/schedules/{schedule}'
 */
changeForm.patch = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteFormDefinition<'post'> => ({
    action: change.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

change.form = changeForm;
/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::runNow
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:58
 * @route '/site-intel/reports/schedules/{schedule}/run'
 */
export const runNow = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteDefinition<'post'> => ({
    url: runNow.url(args, options),
    method: 'post',
});

runNow.definition = {
    methods: ['post'],
    url: '/site-intel/reports/schedules/{schedule}/run',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::runNow
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:58
 * @route '/site-intel/reports/schedules/{schedule}/run'
 */
runNow.url = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { schedule: args };
    }

    if (Array.isArray(args)) {
        args = {
            schedule: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        schedule: args.schedule,
    };

    return (
        runNow.definition.url
            .replace('{schedule}', parsedArgs.schedule.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::runNow
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:58
 * @route '/site-intel/reports/schedules/{schedule}/run'
 */
runNow.post = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteDefinition<'post'> => ({
    url: runNow.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::runNow
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:58
 * @route '/site-intel/reports/schedules/{schedule}/run'
 */
const runNowForm = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteFormDefinition<'post'> => ({
    action: runNow.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::runNow
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:58
 * @route '/site-intel/reports/schedules/{schedule}/run'
 */
runNowForm.post = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteFormDefinition<'post'> => ({
    action: runNow.url(args, options),
    method: 'post',
});

runNow.form = runNowForm;
/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::destroy
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:65
 * @route '/site-intel/reports/schedules/{schedule}'
 */
export const destroy = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/site-intel/reports/schedules/{schedule}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::destroy
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:65
 * @route '/site-intel/reports/schedules/{schedule}'
 */
destroy.url = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { schedule: args };
    }

    if (Array.isArray(args)) {
        args = {
            schedule: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        schedule: args.schedule,
    };

    return (
        destroy.definition.url
            .replace('{schedule}', parsedArgs.schedule.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::destroy
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:65
 * @route '/site-intel/reports/schedules/{schedule}'
 */
destroy.delete = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::destroy
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:65
 * @route '/site-intel/reports/schedules/{schedule}'
 */
const destroyForm = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::destroy
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:65
 * @route '/site-intel/reports/schedules/{schedule}'
 */
destroyForm.delete = (
    args:
        | { schedule: string | number }
        | [schedule: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

destroy.form = destroyForm;
/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::view
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:72
 * @route '/site-intel/reports/{report}/view'
 */
export const view = (
    args:
        | { report: string | number }
        | [report: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: view.url(args, options),
    method: 'get',
});

view.definition = {
    methods: ['get', 'head'],
    url: '/site-intel/reports/{report}/view',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::view
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:72
 * @route '/site-intel/reports/{report}/view'
 */
view.url = (
    args:
        | { report: string | number }
        | [report: string | number]
        | string
        | number,
    options?: RouteQueryOptions
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { report: args };
    }

    if (Array.isArray(args)) {
        args = {
            report: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        report: args.report,
    };

    return (
        view.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::view
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:72
 * @route '/site-intel/reports/{report}/view'
 */
view.get = (
    args:
        | { report: string | number }
        | [report: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: view.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::view
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:72
 * @route '/site-intel/reports/{report}/view'
 */
view.head = (
    args:
        | { report: string | number }
        | [report: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteDefinition<'head'> => ({
    url: view.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::view
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:72
 * @route '/site-intel/reports/{report}/view'
 */
const viewForm = (
    args:
        | { report: string | number }
        | [report: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: view.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::view
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:72
 * @route '/site-intel/reports/{report}/view'
 */
viewForm.get = (
    args:
        | { report: string | number }
        | [report: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: view.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::view
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:72
 * @route '/site-intel/reports/{report}/view'
 */
viewForm.head = (
    args:
        | { report: string | number }
        | [report: string | number]
        | string
        | number,
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: view.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

view.form = viewForm;
/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::download
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:77
 * @route '/site-intel/reports/{report}/download/{format}'
 */
export const download = (
    args:
        | { report: string | number; format: string | number }
        | [report: string | number, format: string | number],
    options?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
});

download.definition = {
    methods: ['get', 'head'],
    url: '/site-intel/reports/{report}/download/{format}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::download
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:77
 * @route '/site-intel/reports/{report}/download/{format}'
 */
download.url = (
    args:
        | { report: string | number; format: string | number }
        | [report: string | number, format: string | number],
    options?: RouteQueryOptions
) => {
    if (Array.isArray(args)) {
        args = {
            report: args[0],
            format: args[1],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        report: args.report,
        format: args.format,
    };

    return (
        download.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace('{format}', parsedArgs.format.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::download
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:77
 * @route '/site-intel/reports/{report}/download/{format}'
 */
download.get = (
    args:
        | { report: string | number; format: string | number }
        | [report: string | number, format: string | number],
    options?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::download
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:77
 * @route '/site-intel/reports/{report}/download/{format}'
 */
download.head = (
    args:
        | { report: string | number; format: string | number }
        | [report: string | number, format: string | number],
    options?: RouteQueryOptions
): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::download
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:77
 * @route '/site-intel/reports/{report}/download/{format}'
 */
const downloadForm = (
    args:
        | { report: string | number; format: string | number }
        | [report: string | number, format: string | number],
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::download
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:77
 * @route '/site-intel/reports/{report}/download/{format}'
 */
downloadForm.get = (
    args:
        | { report: string | number; format: string | number }
        | [report: string | number, format: string | number],
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SiteIntel\SiteIntelReportsController::download
 * @see app/Http/Controllers/SiteIntel/SiteIntelReportsController.php:77
 * @route '/site-intel/reports/{report}/download/{format}'
 */
downloadForm.head = (
    args:
        | { report: string | number; format: string | number }
        | [report: string | number, format: string | number],
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: download.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

download.form = downloadForm;
const SiteIntelReportsController = {
    index,
    store,
    change,
    runNow,
    destroy,
    view,
    download,
};

export default SiteIntelReportsController;
