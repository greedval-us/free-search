import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::index
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/settings/telegram',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::index
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::index
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::index
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::index
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::index
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::index
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
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
* @see \App\Http\Controllers\TelegramBot\SettingsController::issue
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:28
 * @route '/settings/telegram/link'
 */
export const issue = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: issue.url(options),
    method: 'post',
})

issue.definition = {
    methods: ["post"],
    url: '/settings/telegram/link',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::issue
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:28
 * @route '/settings/telegram/link'
 */
issue.url = (options?: RouteQueryOptions) => {
    return issue.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::issue
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:28
 * @route '/settings/telegram/link'
 */
issue.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: issue.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::issue
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:28
 * @route '/settings/telegram/link'
 */
    const issueForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: issue.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::issue
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:28
 * @route '/settings/telegram/link'
 */
        issueForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: issue.url(options),
            method: 'post',
        })
    
    issue.form = issueForm
/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::confirm
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:34
 * @route '/settings/telegram/confirm'
 */
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/settings/telegram/confirm',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::confirm
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:34
 * @route '/settings/telegram/confirm'
 */
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::confirm
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:34
 * @route '/settings/telegram/confirm'
 */
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::confirm
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:34
 * @route '/settings/telegram/confirm'
 */
    const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: confirm.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::confirm
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:34
 * @route '/settings/telegram/confirm'
 */
        confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: confirm.url(options),
            method: 'post',
        })
    
    confirm.form = confirmForm
/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::disconnect
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:41
 * @route '/settings/telegram/link'
 */
export const disconnect = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: disconnect.url(options),
    method: 'delete',
})

disconnect.definition = {
    methods: ["delete"],
    url: '/settings/telegram/link',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::disconnect
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:41
 * @route '/settings/telegram/link'
 */
disconnect.url = (options?: RouteQueryOptions) => {
    return disconnect.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::disconnect
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:41
 * @route '/settings/telegram/link'
 */
disconnect.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: disconnect.url(options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::disconnect
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:41
 * @route '/settings/telegram/link'
 */
    const disconnectForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: disconnect.url({
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::disconnect
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:41
 * @route '/settings/telegram/link'
 */
        disconnectForm.delete = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: disconnect.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    disconnect.form = disconnectForm
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
const SettingsController = { index, status, issue, confirm, disconnect, preferences }

export default SettingsController