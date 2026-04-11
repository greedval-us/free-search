import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Telegram\TelegramSearchController::messages
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:26
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
* @see \App\Http\Controllers\Telegram\TelegramSearchController::messages
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:26
 * @route '/telegram/search/messages'
 */
messages.url = (options?: RouteQueryOptions) => {
    return messages.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramSearchController::messages
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:26
 * @route '/telegram/search/messages'
 */
messages.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: messages.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Telegram\TelegramSearchController::messages
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:26
 * @route '/telegram/search/messages'
 */
messages.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: messages.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramSearchController::messages
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:26
 * @route '/telegram/search/messages'
 */
    const messagesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: messages.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramSearchController::messages
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:26
 * @route '/telegram/search/messages'
 */
        messagesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: messages.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Telegram\TelegramSearchController::messages
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:26
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
/**
* @see \App\Http\Controllers\Telegram\TelegramSearchController::comments
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:58
 * @route '/telegram/search/comments'
 */
export const comments = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: comments.url(options),
    method: 'get',
})

comments.definition = {
    methods: ["get","head"],
    url: '/telegram/search/comments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Telegram\TelegramSearchController::comments
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:58
 * @route '/telegram/search/comments'
 */
comments.url = (options?: RouteQueryOptions) => {
    return comments.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramSearchController::comments
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:58
 * @route '/telegram/search/comments'
 */
comments.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: comments.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Telegram\TelegramSearchController::comments
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:58
 * @route '/telegram/search/comments'
 */
comments.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: comments.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramSearchController::comments
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:58
 * @route '/telegram/search/comments'
 */
    const commentsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: comments.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramSearchController::comments
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:58
 * @route '/telegram/search/comments'
 */
        commentsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: comments.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Telegram\TelegramSearchController::comments
 * @see app/Http/Controllers/Telegram/TelegramSearchController.php:58
 * @route '/telegram/search/comments'
 */
        commentsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: comments.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    comments.form = commentsForm
const search = {
    messages: Object.assign(messages, messages),
comments: Object.assign(comments, comments),
}

export default search