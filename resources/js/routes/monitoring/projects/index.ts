import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:36
 * @route '/monitoring/projects'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/monitoring/projects',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:36
 * @route '/monitoring/projects'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:36
 * @route '/monitoring/projects'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:36
 * @route '/monitoring/projects'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:36
 * @route '/monitoring/projects'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:49
 * @route '/monitoring/projects/{project}'
 */
export const show = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/monitoring/projects/{project}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:49
 * @route '/monitoring/projects/{project}'
 */
show.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:49
 * @route '/monitoring/projects/{project}'
 */
show.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:49
 * @route '/monitoring/projects/{project}'
 */
show.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:49
 * @route '/monitoring/projects/{project}'
 */
    const showForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:49
 * @route '/monitoring/projects/{project}'
 */
        showForm.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::show
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:49
 * @route '/monitoring/projects/{project}'
 */
        showForm.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:54
 * @route '/monitoring/projects/{project}/status'
 */
export const status = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

status.definition = {
    methods: ["get","head"],
    url: '/monitoring/projects/{project}/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::status
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:54
 * @route '/monitoring/projects/{project}/status'
 */
status.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return status.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::status
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:54
 * @route '/monitoring/projects/{project}/status'
 */
status.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::status
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:54
 * @route '/monitoring/projects/{project}/status'
 */
status.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: status.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::status
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:54
 * @route '/monitoring/projects/{project}/status'
 */
    const statusForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: status.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::status
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:54
 * @route '/monitoring/projects/{project}/status'
 */
        statusForm.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: status.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::status
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:54
 * @route '/monitoring/projects/{project}/status'
 */
        statusForm.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\Monitoring\MonitoringController::update
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:59
 * @route '/monitoring/projects/{project}'
 */
export const update = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/monitoring/projects/{project}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::update
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:59
 * @route '/monitoring/projects/{project}'
 */
update.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::update
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:59
 * @route '/monitoring/projects/{project}'
 */
update.patch = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::update
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:59
 * @route '/monitoring/projects/{project}'
 */
    const updateForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: update.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PATCH',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::update
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:59
 * @route '/monitoring/projects/{project}'
 */
        updateForm.patch = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    update.form = updateForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::lifecycle
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:66
 * @route '/monitoring/projects/{project}/lifecycle'
 */
export const lifecycle = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lifecycle.url(args, options),
    method: 'post',
})

lifecycle.definition = {
    methods: ["post"],
    url: '/monitoring/projects/{project}/lifecycle',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::lifecycle
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:66
 * @route '/monitoring/projects/{project}/lifecycle'
 */
lifecycle.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return lifecycle.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::lifecycle
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:66
 * @route '/monitoring/projects/{project}/lifecycle'
 */
lifecycle.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lifecycle.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::lifecycle
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:66
 * @route '/monitoring/projects/{project}/lifecycle'
 */
    const lifecycleForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: lifecycle.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::lifecycle
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:66
 * @route '/monitoring/projects/{project}/lifecycle'
 */
        lifecycleForm.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: lifecycle.url(args, options),
            method: 'post',
        })
    
    lifecycle.form = lifecycleForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::destroy
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:73
 * @route '/monitoring/projects/{project}'
 */
export const destroy = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/monitoring/projects/{project}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::destroy
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:73
 * @route '/monitoring/projects/{project}'
 */
destroy.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return destroy.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::destroy
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:73
 * @route '/monitoring/projects/{project}'
 */
destroy.delete = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::destroy
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:73
 * @route '/monitoring/projects/{project}'
 */
    const destroyForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroy.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::destroy
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:73
 * @route '/monitoring/projects/{project}'
 */
        destroyForm.delete = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroy.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroy.form = destroyForm
const projects = {
    store: Object.assign(store, store),
show: Object.assign(show, show),
status: Object.assign(status, status),
update: Object.assign(update, update),
lifecycle: Object.assign(lifecycle, lifecycle),
destroy: Object.assign(destroy, destroy),
}

export default projects