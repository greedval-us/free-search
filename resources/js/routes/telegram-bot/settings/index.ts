import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::status
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:23
 * @route '/settings/telegram/status'
 */
export const status = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(options),
    method: 'get',
})

status.definition = {
    methods: ["get","head"],
    url: '/settings/telegram/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::status
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:23
 * @route '/settings/telegram/status'
 */
status.url = (options?: RouteQueryOptions) => {
    return status.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::status
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:23
 * @route '/settings/telegram/status'
 */
status.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::status
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:23
 * @route '/settings/telegram/status'
 */
status.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: status.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::status
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:23
 * @route '/settings/telegram/status'
 */
    const statusForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: status.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::status
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:23
 * @route '/settings/telegram/status'
 */
        statusForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: status.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::status
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:23
 * @route '/settings/telegram/status'
 */
        statusForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: status.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    status.form = statusForm
/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::preferences
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:48
 * @route '/settings/telegram/preferences'
 */
export const preferences = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: preferences.url(options),
    method: 'patch',
})

preferences.definition = {
    methods: ["patch"],
    url: '/settings/telegram/preferences',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::preferences
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:48
 * @route '/settings/telegram/preferences'
 */
preferences.url = (options?: RouteQueryOptions) => {
    return preferences.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::preferences
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:48
 * @route '/settings/telegram/preferences'
 */
preferences.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: preferences.url(options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::preferences
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:48
 * @route '/settings/telegram/preferences'
 */
    const preferencesForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: preferences.url({
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PATCH',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::preferences
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:48
 * @route '/settings/telegram/preferences'
 */
        preferencesForm.patch = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: preferences.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    preferences.form = preferencesForm
const settings = {
    status: Object.assign(status, status),
preferences: Object.assign(preferences, preferences),
}

export default settings