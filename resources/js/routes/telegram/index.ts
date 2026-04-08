import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import search from './search'
/**
* @see \App\Http\Controllers\TelegramSearchController::media
 * @see app/Http/Controllers/TelegramSearchController.php:78
 * @route '/telegram/media/{chatUsername}/{messageId}'
 */
export const media = (args: { chatUsername: string | number, messageId: string | number } | [chatUsername: string | number, messageId: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: media.url(args, options),
    method: 'get',
})

media.definition = {
    methods: ["get","head"],
    url: '/telegram/media/{chatUsername}/{messageId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TelegramSearchController::media
 * @see app/Http/Controllers/TelegramSearchController.php:78
 * @route '/telegram/media/{chatUsername}/{messageId}'
 */
media.url = (args: { chatUsername: string | number, messageId: string | number } | [chatUsername: string | number, messageId: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    chatUsername: args[0],
                    messageId: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        chatUsername: args.chatUsername,
                                messageId: args.messageId,
                }

    return media.definition.url
            .replace('{chatUsername}', parsedArgs.chatUsername.toString())
            .replace('{messageId}', parsedArgs.messageId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelegramSearchController::media
 * @see app/Http/Controllers/TelegramSearchController.php:78
 * @route '/telegram/media/{chatUsername}/{messageId}'
 */
media.get = (args: { chatUsername: string | number, messageId: string | number } | [chatUsername: string | number, messageId: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: media.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\TelegramSearchController::media
 * @see app/Http/Controllers/TelegramSearchController.php:78
 * @route '/telegram/media/{chatUsername}/{messageId}'
 */
media.head = (args: { chatUsername: string | number, messageId: string | number } | [chatUsername: string | number, messageId: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: media.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\TelegramSearchController::media
 * @see app/Http/Controllers/TelegramSearchController.php:78
 * @route '/telegram/media/{chatUsername}/{messageId}'
 */
    const mediaForm = (args: { chatUsername: string | number, messageId: string | number } | [chatUsername: string | number, messageId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: media.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\TelegramSearchController::media
 * @see app/Http/Controllers/TelegramSearchController.php:78
 * @route '/telegram/media/{chatUsername}/{messageId}'
 */
        mediaForm.get = (args: { chatUsername: string | number, messageId: string | number } | [chatUsername: string | number, messageId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: media.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\TelegramSearchController::media
 * @see app/Http/Controllers/TelegramSearchController.php:78
 * @route '/telegram/media/{chatUsername}/{messageId}'
 */
        mediaForm.head = (args: { chatUsername: string | number, messageId: string | number } | [chatUsername: string | number, messageId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: media.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    media.form = mediaForm
const telegram = {
    search: Object.assign(search, search),
media: Object.assign(media, media),
}

export default telegram