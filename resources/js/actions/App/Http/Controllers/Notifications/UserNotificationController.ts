import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markAllRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:12
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
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:12
 * @route '/notifications/read-all'
 */
markAllRead.url = (options?: RouteQueryOptions) => {
    return markAllRead.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markAllRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:12
 * @route '/notifications/read-all'
 */
markAllRead.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllRead.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markAllRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:12
 * @route '/notifications/read-all'
 */
    const markAllReadForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: markAllRead.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markAllRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:12
 * @route '/notifications/read-all'
 */
        markAllReadForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: markAllRead.url(options),
            method: 'post',
        })
    
    markAllRead.form = markAllReadForm
/**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:19
 * @route '/notifications/{notification}/read'
 */
export const markRead = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markRead.url(args, options),
    method: 'post',
})

markRead.definition = {
    methods: ["post"],
    url: '/notifications/{notification}/read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:19
 * @route '/notifications/{notification}/read'
 */
markRead.url = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { notification: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    notification: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        notification: args.notification,
                }

    return markRead.definition.url
            .replace('{notification}', parsedArgs.notification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:19
 * @route '/notifications/{notification}/read'
 */
markRead.post = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markRead.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:19
 * @route '/notifications/{notification}/read'
 */
    const markReadForm = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: markRead.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Notifications\UserNotificationController::markRead
 * @see app/Http/Controllers/Notifications/UserNotificationController.php:19
 * @route '/notifications/{notification}/read'
 */
        markReadForm.post = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: markRead.url(args, options),
            method: 'post',
        })
    
    markRead.form = markReadForm
const UserNotificationController = { markAllRead, markRead }

export default UserNotificationController