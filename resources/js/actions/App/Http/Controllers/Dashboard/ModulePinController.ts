import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Dashboard\ModulePinController::toggle
 * @see app/Http/Controllers/Dashboard/ModulePinController.php:18
 * @route '/dashboard/module-pins/toggle'
 */
export const toggle = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggle.url(options),
    method: 'post',
})

toggle.definition = {
    methods: ["post"],
    url: '/dashboard/module-pins/toggle',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Dashboard\ModulePinController::toggle
 * @see app/Http/Controllers/Dashboard/ModulePinController.php:18
 * @route '/dashboard/module-pins/toggle'
 */
toggle.url = (options?: RouteQueryOptions) => {
    return toggle.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Dashboard\ModulePinController::toggle
 * @see app/Http/Controllers/Dashboard/ModulePinController.php:18
 * @route '/dashboard/module-pins/toggle'
 */
toggle.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggle.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Dashboard\ModulePinController::toggle
 * @see app/Http/Controllers/Dashboard/ModulePinController.php:18
 * @route '/dashboard/module-pins/toggle'
 */
    const toggleForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: toggle.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Dashboard\ModulePinController::toggle
 * @see app/Http/Controllers/Dashboard/ModulePinController.php:18
 * @route '/dashboard/module-pins/toggle'
 */
        toggleForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: toggle.url(options),
            method: 'post',
        })
    
    toggle.form = toggleForm
const ModulePinController = { toggle }

export default ModulePinController