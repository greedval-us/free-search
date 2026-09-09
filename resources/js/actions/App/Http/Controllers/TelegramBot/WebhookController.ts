import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\TelegramBot\WebhookController::__invoke
 * @see app/Http/Controllers/TelegramBot/WebhookController.php:14
 * @route '/integrations/telegram-bot/webhook'
 */
const WebhookController = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: WebhookController.url(options),
    method: 'post',
})

WebhookController.definition = {
    methods: ["post"],
    url: '/integrations/telegram-bot/webhook',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TelegramBot\WebhookController::__invoke
 * @see app/Http/Controllers/TelegramBot/WebhookController.php:14
 * @route '/integrations/telegram-bot/webhook'
 */
WebhookController.url = (options?: RouteQueryOptions) => {
    return WebhookController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelegramBot\WebhookController::__invoke
 * @see app/Http/Controllers/TelegramBot/WebhookController.php:14
 * @route '/integrations/telegram-bot/webhook'
 */
WebhookController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: WebhookController.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\TelegramBot\WebhookController::__invoke
 * @see app/Http/Controllers/TelegramBot/WebhookController.php:14
 * @route '/integrations/telegram-bot/webhook'
 */
    const WebhookControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: WebhookController.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\TelegramBot\WebhookController::__invoke
 * @see app/Http/Controllers/TelegramBot/WebhookController.php:14
 * @route '/integrations/telegram-bot/webhook'
 */
        WebhookControllerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: WebhookController.url(options),
            method: 'post',
        })
    
    WebhookController.form = WebhookControllerForm
export default WebhookController