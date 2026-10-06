import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import projects from './projects'
import sources from './sources'
import schedules from './schedules'
import reports from './reports'
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
const monitoring = {
    index: Object.assign(index, index),
projects: Object.assign(projects, projects),
sources: Object.assign(sources, sources),
schedules: Object.assign(schedules, schedules),
reports: Object.assign(reports, reports),
history: Object.assign(history, history),
materials: Object.assign(materials, materials),
}

export default monitoring