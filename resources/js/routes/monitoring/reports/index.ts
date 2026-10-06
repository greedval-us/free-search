import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:112
 * @route '/monitoring/projects/{project}/reports'
 */
export const store = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/monitoring/projects/{project}/reports',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:112
 * @route '/monitoring/projects/{project}/reports'
 */
store.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    project: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        project: args.project,
                }

    return store.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:112
 * @route '/monitoring/projects/{project}/reports'
 */
store.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:112
 * @route '/monitoring/projects/{project}/reports'
 */
    const storeForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:112
 * @route '/monitoring/projects/{project}/reports'
 */
        storeForm.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(args, options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
export const show = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/monitoring/reports/{report}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
show.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { report: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    report: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        report: args.report,
                }

    return show.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
show.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
show.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
    const showForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
        showForm.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
        showForm.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    show.form = showForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::status
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
export const status = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

status.definition = {
    methods: ["get","head"],
    url: '/monitoring/reports/{report}/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::status
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
status.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { report: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    report: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        report: args.report,
                }

    return status.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::status
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
status.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::status
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
status.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: status.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::status
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
    const statusForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: status.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::status
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
        statusForm.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: status.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::status
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
        statusForm.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: status.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    status.form = statusForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::download
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:189
 * @route '/monitoring/reports/{report}/download/{format}'
 */
export const download = (args: { report: string | number, format: string | number } | [report: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/monitoring/reports/{report}/download/{format}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::download
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:189
 * @route '/monitoring/reports/{report}/download/{format}'
 */
download.url = (args: { report: string | number, format: string | number } | [report: string | number, format: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    report: args[0],
                    format: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        report: args.report,
                                format: args.format,
                }

    return download.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace('{format}', parsedArgs.format.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::download
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:189
 * @route '/monitoring/reports/{report}/download/{format}'
 */
download.get = (args: { report: string | number, format: string | number } | [report: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::download
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:189
 * @route '/monitoring/reports/{report}/download/{format}'
 */
download.head = (args: { report: string | number, format: string | number } | [report: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::download
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:189
 * @route '/monitoring/reports/{report}/download/{format}'
 */
    const downloadForm = (args: { report: string | number, format: string | number } | [report: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: download.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::download
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:189
 * @route '/monitoring/reports/{report}/download/{format}'
 */
        downloadForm.get = (args: { report: string | number, format: string | number } | [report: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: download.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::download
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:189
 * @route '/monitoring/reports/{report}/download/{format}'
 */
        downloadForm.head = (args: { report: string | number, format: string | number } | [report: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: download.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    download.form = downloadForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::regenerate
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:120
 * @route '/monitoring/reports/{report}/regenerate'
 */
export const regenerate = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: regenerate.url(args, options),
    method: 'post',
})

regenerate.definition = {
    methods: ["post"],
    url: '/monitoring/reports/{report}/regenerate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::regenerate
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:120
 * @route '/monitoring/reports/{report}/regenerate'
 */
regenerate.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { report: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    report: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        report: args.report,
                }

    return regenerate.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::regenerate
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:120
 * @route '/monitoring/reports/{report}/regenerate'
 */
regenerate.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: regenerate.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::regenerate
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:120
 * @route '/monitoring/reports/{report}/regenerate'
 */
    const regenerateForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: regenerate.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::regenerate
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:120
 * @route '/monitoring/reports/{report}/regenerate'
 */
        regenerateForm.post = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: regenerate.url(args, options),
            method: 'post',
        })
    
    regenerate.form = regenerateForm
const reports = {
    store: Object.assign(store, store),
show: Object.assign(show, show),
status: Object.assign(status, status),
download: Object.assign(download, download),
regenerate: Object.assign(regenerate, regenerate),
}

export default reports