import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markAllRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:11
 * @route '/notifications/read-all'
 */
export const markAllRead = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllRead.url(options),
    method: 'post',
})

markAllRead.definition = {
    methods: ["post"],
    url: '/notifications/read-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markAllRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:11
 * @route '/notifications/read-all'
 */
markAllRead.url = (options?: RouteQueryOptions) => {
    return markAllRead.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markAllRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:11
 * @route '/notifications/read-all'
 */
markAllRead.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllRead.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markAllRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:11
 * @route '/notifications/read-all'
 */
    const markAllReadForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: markAllRead.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markAllRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:11
 * @route '/notifications/read-all'
 */
        markAllReadForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: markAllRead.url(options),
            method: 'post',
        })
    
    markAllRead.form = markAllReadForm
const UserNotificationController = { markAllRead }

export default UserNotificationController