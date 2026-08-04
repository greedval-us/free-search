import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\Notifications\UserNotificationController::readAll
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:11
 * @route '/notifications/read-all'
 */
export const readAll = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAll.url(options),
    method: 'post',
})

readAll.definition = {
    methods: ["post"],
    url: '/notifications/read-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Notifications\UserNotificationController::readAll
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:11
 * @route '/notifications/read-all'
 */
readAll.url = (options?: RouteQueryOptions) => {
    return readAll.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Notifications\UserNotificationController::readAll
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:11
 * @route '/notifications/read-all'
 */
readAll.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAll.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Notifications\UserNotificationController::readAll
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:11
 * @route '/notifications/read-all'
 */
    const readAllForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: readAll.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Notifications\UserNotificationController::readAll
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:11
 * @route '/notifications/read-all'
 */
        readAllForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: readAll.url(options),
            method: 'post',
        })
    
    readAll.form = readAllForm
const notifications = {
    readAll: Object.assign(readAll, readAll),
}

export default notifications