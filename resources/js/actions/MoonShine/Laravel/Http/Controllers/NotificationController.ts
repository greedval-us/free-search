import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::readAll
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:11
 * @route '/admin/notifications'
 */
export const readAll = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: readAll.url(options),
    method: 'get',
})

readAll.definition = {
    methods: ["get","head"],
    url: '/admin/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::readAll
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:11
 * @route '/admin/notifications'
 */
readAll.url = (options?: RouteQueryOptions) => {
    return readAll.definition.url + queryParams(options)
}

/**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::readAll
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:11
 * @route '/admin/notifications'
 */
readAll.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: readAll.url(options),
    method: 'get',
})
/**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::readAll
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:11
 * @route '/admin/notifications'
 */
readAll.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: readAll.url(options),
    method: 'head',
})

    /**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::readAll
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:11
 * @route '/admin/notifications'
 */
    const readAllForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: readAll.url(options),
        method: 'get',
    })

            /**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::readAll
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:11
 * @route '/admin/notifications'
 */
        readAllForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: readAll.url(options),
            method: 'get',
        })
            /**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::readAll
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:11
 * @route '/admin/notifications'
 */
        readAllForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: readAll.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    readAll.form = readAllForm
/**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::read
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:18
 * @route '/admin/notifications/{notification}'
 */
export const read = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: read.url(args, options),
    method: 'get',
})

read.definition = {
    methods: ["get","head"],
    url: '/admin/notifications/{notification}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::read
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:18
 * @route '/admin/notifications/{notification}'
 */
read.url = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return read.definition.url
            .replace('{notification}', parsedArgs.notification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::read
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:18
 * @route '/admin/notifications/{notification}'
 */
read.get = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: read.url(args, options),
    method: 'get',
})
/**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::read
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:18
 * @route '/admin/notifications/{notification}'
 */
read.head = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: read.url(args, options),
    method: 'head',
})

    /**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::read
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:18
 * @route '/admin/notifications/{notification}'
 */
    const readForm = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: read.url(args, options),
        method: 'get',
    })

            /**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::read
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:18
 * @route '/admin/notifications/{notification}'
 */
        readForm.get = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: read.url(args, options),
            method: 'get',
        })
            /**
* @see \MoonShine\Laravel\Http\Controllers\NotificationController::read
 * @see vendor/moonshine/moonshine/src/Laravel/src/Http/Controllers/NotificationController.php:18
 * @route '/admin/notifications/{notification}'
 */
        readForm.head = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: read.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    read.form = readForm
const NotificationController = { readAll, read }

export default NotificationController