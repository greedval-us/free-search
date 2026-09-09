import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
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
const link = {
    issue: Object.assign(issue, issue),
confirm: Object.assign(confirm, confirm),
disconnect: Object.assign(disconnect, disconnect),
}

export default link