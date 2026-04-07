import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\TelegramSearchController::messages
 * @see app/Http/Controllers/TelegramSearchController.php:17
 * @route '/telegram/search/messages'
 */
export const messages = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: messages.url(options),
    method: 'get',
})

messages.definition = {
    methods: ["get","head"],
    url: '/telegram/search/messages',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TelegramSearchController::messages
 * @see app/Http/Controllers/TelegramSearchController.php:17
 * @route '/telegram/search/messages'
 */
messages.url = (options?: RouteQueryOptions) => {
    return messages.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelegramSearchController::messages
 * @see app/Http/Controllers/TelegramSearchController.php:17
 * @route '/telegram/search/messages'
 */
messages.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: messages.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\TelegramSearchController::messages
 * @see app/Http/Controllers/TelegramSearchController.php:17
 * @route '/telegram/search/messages'
 */
messages.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: messages.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\TelegramSearchController::messages
 * @see app/Http/Controllers/TelegramSearchController.php:17
 * @route '/telegram/search/messages'
 */
    const messagesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: messages.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\TelegramSearchController::messages
 * @see app/Http/Controllers/TelegramSearchController.php:17
 * @route '/telegram/search/messages'
 */
        messagesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: messages.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\TelegramSearchController::messages
 * @see app/Http/Controllers/TelegramSearchController.php:17
 * @route '/telegram/search/messages'
 */
        messagesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: messages.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    messages.form = messagesForm
const search = {
    messages: Object.assign(messages, messages),
}

export default search