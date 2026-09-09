import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import settings69f00b from './settings'
import link from './link'
/**
* @see \App\Http\Controllers\TelegramBot\WebhookController::__invoke
 * @see app/Http/Controllers/TelegramBot/WebhookController.php:14
 * @route '/integrations/telegram-bot/webhook'
 */
export const webhook = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: webhook.url(options),
    method: 'post',
})

webhook.definition = {
    methods: ["post"],
    url: '/integrations/telegram-bot/webhook',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TelegramBot\WebhookController::__invoke
 * @see app/Http/Controllers/TelegramBot/WebhookController.php:14
 * @route '/integrations/telegram-bot/webhook'
 */
webhook.url = (options?: RouteQueryOptions) => {
    return webhook.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelegramBot\WebhookController::__invoke
 * @see app/Http/Controllers/TelegramBot/WebhookController.php:14
 * @route '/integrations/telegram-bot/webhook'
 */
webhook.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: webhook.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\TelegramBot\WebhookController::__invoke
 * @see app/Http/Controllers/TelegramBot/WebhookController.php:14
 * @route '/integrations/telegram-bot/webhook'
 */
    const webhookForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: webhook.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\TelegramBot\WebhookController::__invoke
 * @see app/Http/Controllers/TelegramBot/WebhookController.php:14
 * @route '/integrations/telegram-bot/webhook'
 */
        webhookForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: webhook.url(options),
            method: 'post',
        })
    
    webhook.form = webhookForm
/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::settings
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
 */
export const settings = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: settings.url(options),
    method: 'get',
})

settings.definition = {
    methods: ["get","head"],
    url: '/settings/telegram',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::settings
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
 */
settings.url = (options?: RouteQueryOptions) => {
    return settings.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::settings
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
 */
settings.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: settings.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\TelegramBot\SettingsController::settings
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
 */
settings.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: settings.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::settings
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
 */
    const settingsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: settings.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::settings
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
 */
        settingsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: settings.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\TelegramBot\SettingsController::settings
 * @see app/Http/Controllers/TelegramBot/SettingsController.php:18
 * @route '/settings/telegram'
 */
        settingsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: settings.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    settings.form = settingsForm
const telegramBot = {
    webhook: Object.assign(webhook, webhook),
settings: Object.assign(settings, settings69f00b),
link: Object.assign(link, link),
}

export default telegramBot