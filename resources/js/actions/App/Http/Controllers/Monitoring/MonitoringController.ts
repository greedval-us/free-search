import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::index
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:25
 * @route '/monitoring'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/monitoring',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::index
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:25
 * @route '/monitoring'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::index
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:25
 * @route '/monitoring'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::index
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:25
 * @route '/monitoring'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::index
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:25
 * @route '/monitoring'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::index
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:25
 * @route '/monitoring'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::index
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:25
 * @route '/monitoring'
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
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::addSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:80
 * @route '/monitoring/projects/{project}/sources'
 */
export const addSource = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: addSource.url(args, options),
    method: 'post',
})

addSource.definition = {
    methods: ["post"],
    url: '/monitoring/projects/{project}/sources',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::addSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:80
 * @route '/monitoring/projects/{project}/sources'
 */
addSource.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return addSource.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::addSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:80
 * @route '/monitoring/projects/{project}/sources'
 */
addSource.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: addSource.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::addSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:80
 * @route '/monitoring/projects/{project}/sources'
 */
    const addSourceForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: addSource.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::addSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:80
 * @route '/monitoring/projects/{project}/sources'
 */
        addSourceForm.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: addSource.url(args, options),
            method: 'post',
        })
    
    addSource.form = addSourceForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::validateSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:87
 * @route '/monitoring/projects/{project}/sources/{source}/validate'
 */
export const validateSource = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validateSource.url(args, options),
    method: 'post',
})

validateSource.definition = {
    methods: ["post"],
    url: '/monitoring/projects/{project}/sources/{source}/validate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::validateSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:87
 * @route '/monitoring/projects/{project}/sources/{source}/validate'
 */
validateSource.url = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    project: args[0],
                    source: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        project: args.project,
                                source: args.source,
                }

    return validateSource.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{source}', parsedArgs.source.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::validateSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:87
 * @route '/monitoring/projects/{project}/sources/{source}/validate'
 */
validateSource.post = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validateSource.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::validateSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:87
 * @route '/monitoring/projects/{project}/sources/{source}/validate'
 */
    const validateSourceForm = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: validateSource.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::validateSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:87
 * @route '/monitoring/projects/{project}/sources/{source}/validate'
 */
        validateSourceForm.post = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: validateSource.url(args, options),
            method: 'post',
        })
    
    validateSource.form = validateSourceForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::removeSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:95
 * @route '/monitoring/projects/{project}/sources/{source}'
 */
export const removeSource = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: removeSource.url(args, options),
    method: 'delete',
})

removeSource.definition = {
    methods: ["delete"],
    url: '/monitoring/projects/{project}/sources/{source}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::removeSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:95
 * @route '/monitoring/projects/{project}/sources/{source}'
 */
removeSource.url = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    project: args[0],
                    source: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        project: args.project,
                                source: args.source,
                }

    return removeSource.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{source}', parsedArgs.source.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::removeSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:95
 * @route '/monitoring/projects/{project}/sources/{source}'
 */
removeSource.delete = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: removeSource.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::removeSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:95
 * @route '/monitoring/projects/{project}/sources/{source}'
 */
    const removeSourceForm = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: removeSource.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::removeSource
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:95
 * @route '/monitoring/projects/{project}/sources/{source}'
 */
        removeSourceForm.delete = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: removeSource.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    removeSource.form = removeSourceForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::saveSchedule
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules'
 */
const saveSchedule5b8824dfb535c70654673391e8fbcbcb = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveSchedule5b8824dfb535c70654673391e8fbcbcb.url(args, options),
    method: 'post',
})

saveSchedule5b8824dfb535c70654673391e8fbcbcb.definition = {
    methods: ["post"],
    url: '/monitoring/projects/{project}/schedules',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::saveSchedule
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules'
 */
saveSchedule5b8824dfb535c70654673391e8fbcbcb.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return saveSchedule5b8824dfb535c70654673391e8fbcbcb.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::saveSchedule
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules'
 */
saveSchedule5b8824dfb535c70654673391e8fbcbcb.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveSchedule5b8824dfb535c70654673391e8fbcbcb.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::saveSchedule
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules'
 */
    const saveSchedule5b8824dfb535c70654673391e8fbcbcbForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: saveSchedule5b8824dfb535c70654673391e8fbcbcb.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::saveSchedule
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules'
 */
        saveSchedule5b8824dfb535c70654673391e8fbcbcbForm.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: saveSchedule5b8824dfb535c70654673391e8fbcbcb.url(args, options),
            method: 'post',
        })
    
    saveSchedule5b8824dfb535c70654673391e8fbcbcb.form = saveSchedule5b8824dfb535c70654673391e8fbcbcbForm
    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::saveSchedule
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules/{schedule}'
 */
const saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8 = (args: { project: string | number, schedule: string | number } | [project: string | number, schedule: string | number ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8.url(args, options),
    method: 'patch',
})

saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8.definition = {
    methods: ["patch"],
    url: '/monitoring/projects/{project}/schedules/{schedule}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::saveSchedule
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules/{schedule}'
 */
saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8.url = (args: { project: string | number, schedule: string | number } | [project: string | number, schedule: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    project: args[0],
                    schedule: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        project: args.project,
                                schedule: args.schedule,
                }

    return saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{schedule}', parsedArgs.schedule.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::saveSchedule
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules/{schedule}'
 */
saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8.patch = (args: { project: string | number, schedule: string | number } | [project: string | number, schedule: string | number ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::saveSchedule
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules/{schedule}'
 */
    const saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8Form = (args: { project: string | number, schedule: string | number } | [project: string | number, schedule: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PATCH',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::saveSchedule
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules/{schedule}'
 */
        saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8Form.patch = (args: { project: string | number, schedule: string | number } | [project: string | number, schedule: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8.form = saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8Form

/**
* Multiple routes resolve to \App\Http\Controllers\Monitoring\MonitoringController::saveSchedule, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `saveSchedule['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const saveSchedule = {
    '/monitoring/projects/{project}/schedules': saveSchedule5b8824dfb535c70654673391e8fbcbcb,
    '/monitoring/projects/{project}/schedules/{schedule}': saveSchedulea1d9daf0f5b8947890fe673f2e33b0f8,
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::requestReport
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:112
 * @route '/monitoring/projects/{project}/reports'
 */
export const requestReport = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestReport.url(args, options),
    method: 'post',
})

requestReport.definition = {
    methods: ["post"],
    url: '/monitoring/projects/{project}/reports',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::requestReport
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:112
 * @route '/monitoring/projects/{project}/reports'
 */
requestReport.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return requestReport.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::requestReport
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:112
 * @route '/monitoring/projects/{project}/reports'
 */
requestReport.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestReport.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::requestReport
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:112
 * @route '/monitoring/projects/{project}/reports'
 */
    const requestReportForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: requestReport.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::requestReport
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:112
 * @route '/monitoring/projects/{project}/reports'
 */
        requestReportForm.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: requestReport.url(args, options),
            method: 'post',
        })
    
    requestReport.form = requestReportForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::history
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:129
 * @route '/monitoring/history'
 */
export const history = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(options),
    method: 'get',
})

history.definition = {
    methods: ["get","head"],
    url: '/monitoring/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::history
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:129
 * @route '/monitoring/history'
 */
history.url = (options?: RouteQueryOptions) => {
    return history.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::history
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:129
 * @route '/monitoring/history'
 */
history.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::history
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:129
 * @route '/monitoring/history'
 */
history.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: history.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::history
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:129
 * @route '/monitoring/history'
 */
    const historyForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: history.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::history
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:129
 * @route '/monitoring/history'
 */
        historyForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: history.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::history
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:129
 * @route '/monitoring/history'
 */
        historyForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: history.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    history.form = historyForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::materials
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:155
 * @route '/monitoring/projects/{project}/materials'
 */
export const materials = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: materials.url(args, options),
    method: 'get',
})

materials.definition = {
    methods: ["get","head"],
    url: '/monitoring/projects/{project}/materials',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::materials
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:155
 * @route '/monitoring/projects/{project}/materials'
 */
materials.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return materials.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::materials
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:155
 * @route '/monitoring/projects/{project}/materials'
 */
materials.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: materials.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::materials
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:155
 * @route '/monitoring/projects/{project}/materials'
 */
materials.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: materials.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::materials
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:155
 * @route '/monitoring/projects/{project}/materials'
 */
    const materialsForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: materials.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::materials
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:155
 * @route '/monitoring/projects/{project}/materials'
 */
        materialsForm.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: materials.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::materials
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:155
 * @route '/monitoring/projects/{project}/materials'
 */
        materialsForm.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: materials.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    materials.form = materialsForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::report
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
export const report = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(args, options),
    method: 'get',
})

report.definition = {
    methods: ["get","head"],
    url: '/monitoring/reports/{report}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::report
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
report.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return report.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::report
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
report.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::report
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
report.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: report.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::report
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
    const reportForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: report.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::report
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
        reportForm.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: report.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::report
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:173
 * @route '/monitoring/reports/{report}'
 */
        reportForm.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: report.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    report.form = reportForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::reportStatus
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
export const reportStatus = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reportStatus.url(args, options),
    method: 'get',
})

reportStatus.definition = {
    methods: ["get","head"],
    url: '/monitoring/reports/{report}/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::reportStatus
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
reportStatus.url = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return reportStatus.definition.url
            .replace('{report}', parsedArgs.report.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::reportStatus
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
reportStatus.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reportStatus.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::reportStatus
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
reportStatus.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: reportStatus.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::reportStatus
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
    const reportStatusForm = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: reportStatus.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::reportStatus
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
        reportStatusForm.get = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: reportStatus.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::reportStatus
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:184
 * @route '/monitoring/reports/{report}/status'
 */
        reportStatusForm.head = (args: { report: string | number } | [report: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: reportStatus.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    reportStatus.form = reportStatusForm
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
const MonitoringController = { index, store, show, status, update, lifecycle, destroy, addSource, validateSource, removeSource, saveSchedule, requestReport, history, materials, report, reportStatus, download, regenerate }

export default MonitoringController