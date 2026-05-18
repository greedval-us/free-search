import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
export const modules = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: modules.url(options),
    method: 'get',
})

modules.definition = {
    methods: ["get","head"],
    url: '/wiki/modules',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
modules.url = (options?: RouteQueryOptions) => {
    return modules.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
modules.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: modules.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
modules.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: modules.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
    const modulesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: modules.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
        modulesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: modules.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
        modulesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: modules.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    modules.form = modulesForm
const wiki = {
    modules: Object.assign(modules, modules),
}

export default wiki