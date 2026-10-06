import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:80
 * @route '/monitoring/projects/{project}/sources'
 */
export const store = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/monitoring/projects/{project}/sources',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:80
 * @route '/monitoring/projects/{project}/sources'
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
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:80
 * @route '/monitoring/projects/{project}/sources'
 */
store.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:80
 * @route '/monitoring/projects/{project}/sources'
 */
    const storeForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:80
 * @route '/monitoring/projects/{project}/sources'
 */
        storeForm.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(args, options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::validate
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:87
 * @route '/monitoring/projects/{project}/sources/{source}/validate'
 */
export const validate = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validate.url(args, options),
    method: 'post',
})

validate.definition = {
    methods: ["post"],
    url: '/monitoring/projects/{project}/sources/{source}/validate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::validate
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:87
 * @route '/monitoring/projects/{project}/sources/{source}/validate'
 */
validate.url = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions) => {
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

    return validate.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{source}', parsedArgs.source.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::validate
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:87
 * @route '/monitoring/projects/{project}/sources/{source}/validate'
 */
validate.post = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validate.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::validate
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:87
 * @route '/monitoring/projects/{project}/sources/{source}/validate'
 */
    const validateForm = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: validate.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::validate
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:87
 * @route '/monitoring/projects/{project}/sources/{source}/validate'
 */
        validateForm.post = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: validate.url(args, options),
            method: 'post',
        })
    
    validate.form = validateForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::destroy
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:95
 * @route '/monitoring/projects/{project}/sources/{source}'
 */
export const destroy = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/monitoring/projects/{project}/sources/{source}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::destroy
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:95
 * @route '/monitoring/projects/{project}/sources/{source}'
 */
destroy.url = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions) => {
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

    return destroy.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{source}', parsedArgs.source.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::destroy
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:95
 * @route '/monitoring/projects/{project}/sources/{source}'
 */
destroy.delete = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::destroy
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:95
 * @route '/monitoring/projects/{project}/sources/{source}'
 */
    const destroyForm = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:95
 * @route '/monitoring/projects/{project}/sources/{source}'
 */
        destroyForm.delete = (args: { project: string | number, source: string | number } | [project: string | number, source: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroy.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroy.form = destroyForm
const sources = {
    store: Object.assign(store, store),
validate: Object.assign(validate, validate),
destroy: Object.assign(destroy, destroy),
}

export default sources