import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules'
 */
export const store = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/monitoring/projects/{project}/schedules',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules'
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
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules'
 */
store.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules'
 */
    const storeForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::store
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules'
 */
        storeForm.post = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(args, options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::update
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules/{schedule}'
 */
export const update = (args: { project: string | number, schedule: string | number } | [project: string | number, schedule: string | number ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/monitoring/projects/{project}/schedules/{schedule}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::update
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules/{schedule}'
 */
update.url = (args: { project: string | number, schedule: string | number } | [project: string | number, schedule: string | number ], options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{schedule}', parsedArgs.schedule.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MonitoringController::update
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules/{schedule}'
 */
update.patch = (args: { project: string | number, schedule: string | number } | [project: string | number, schedule: string | number ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\Monitoring\MonitoringController::update
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules/{schedule}'
 */
    const updateForm = (args: { project: string | number, schedule: string | number } | [project: string | number, schedule: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
 * @see app/Http/Controllers/Monitoring/MonitoringController.php:103
 * @route '/monitoring/projects/{project}/schedules/{schedule}'
 */
        updateForm.patch = (args: { project: string | number, schedule: string | number } | [project: string | number, schedule: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    update.form = updateForm
const schedules = {
    store: Object.assign(store, store),
update: Object.assign(update, update),
}

export default schedules