import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::index
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:23
 * @route '/telegram/tracking'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/telegram/tracking',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::index
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:23
 * @route '/telegram/tracking'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::index
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:23
 * @route '/telegram/tracking'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::index
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:23
 * @route '/telegram/tracking'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::index
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:23
 * @route '/telegram/tracking'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::index
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:23
 * @route '/telegram/tracking'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::index
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:23
 * @route '/telegram/tracking'
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
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::validate
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:36
 * @route '/telegram/tracking/validate'
 */
export const validate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validate.url(options),
    method: 'post',
})

validate.definition = {
    methods: ["post"],
    url: '/telegram/tracking/validate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::validate
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:36
 * @route '/telegram/tracking/validate'
 */
validate.url = (options?: RouteQueryOptions) => {
    return validate.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::validate
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:36
 * @route '/telegram/tracking/validate'
 */
validate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validate.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::validate
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:36
 * @route '/telegram/tracking/validate'
 */
    const validateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: validate.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::validate
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:36
 * @route '/telegram/tracking/validate'
 */
        validateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: validate.url(options),
            method: 'post',
        })
    
    validate.form = validateForm
/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::store
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:45
 * @route '/telegram/tracking'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/telegram/tracking',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::store
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:45
 * @route '/telegram/tracking'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::store
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:45
 * @route '/telegram/tracking'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::store
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:45
 * @route '/telegram/tracking'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::store
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:45
 * @route '/telegram/tracking'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::change
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:52
 * @route '/telegram/tracking/{tracking}'
 */
export const change = (args: { tracking: string | number } | [tracking: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: change.url(args, options),
    method: 'patch',
})

change.definition = {
    methods: ["patch"],
    url: '/telegram/tracking/{tracking}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::change
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:52
 * @route '/telegram/tracking/{tracking}'
 */
change.url = (args: { tracking: string | number } | [tracking: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tracking: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    tracking: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        tracking: args.tracking,
                }

    return change.definition.url
            .replace('{tracking}', parsedArgs.tracking.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::change
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:52
 * @route '/telegram/tracking/{tracking}'
 */
change.patch = (args: { tracking: string | number } | [tracking: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: change.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::change
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:52
 * @route '/telegram/tracking/{tracking}'
 */
    const changeForm = (args: { tracking: string | number } | [tracking: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: change.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PATCH',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::change
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:52
 * @route '/telegram/tracking/{tracking}'
 */
        changeForm.patch = (args: { tracking: string | number } | [tracking: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: change.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    change.form = changeForm
/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::messages
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:59
 * @route '/telegram/tracking/{tracking}/messages'
 */
export const messages = (args: { tracking: string | number } | [tracking: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: messages.url(args, options),
    method: 'get',
})

messages.definition = {
    methods: ["get","head"],
    url: '/telegram/tracking/{tracking}/messages',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::messages
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:59
 * @route '/telegram/tracking/{tracking}/messages'
 */
messages.url = (args: { tracking: string | number } | [tracking: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tracking: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    tracking: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        tracking: args.tracking,
                }

    return messages.definition.url
            .replace('{tracking}', parsedArgs.tracking.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::messages
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:59
 * @route '/telegram/tracking/{tracking}/messages'
 */
messages.get = (args: { tracking: string | number } | [tracking: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: messages.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::messages
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:59
 * @route '/telegram/tracking/{tracking}/messages'
 */
messages.head = (args: { tracking: string | number } | [tracking: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: messages.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::messages
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:59
 * @route '/telegram/tracking/{tracking}/messages'
 */
    const messagesForm = (args: { tracking: string | number } | [tracking: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: messages.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::messages
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:59
 * @route '/telegram/tracking/{tracking}/messages'
 */
        messagesForm.get = (args: { tracking: string | number } | [tracking: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: messages.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::messages
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:59
 * @route '/telegram/tracking/{tracking}/messages'
 */
        messagesForm.head = (args: { tracking: string | number } | [tracking: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: messages.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    messages.form = messagesForm
/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::download
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:67
 * @route '/telegram/tracking/{tracking}/export/{format}'
 */
export const download = (args: { tracking: string | number, format: string | number } | [tracking: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/telegram/tracking/{tracking}/export/{format}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::download
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:67
 * @route '/telegram/tracking/{tracking}/export/{format}'
 */
download.url = (args: { tracking: string | number, format: string | number } | [tracking: string | number, format: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    tracking: args[0],
                    format: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        tracking: args.tracking,
                                format: args.format,
                }

    return download.definition.url
            .replace('{tracking}', parsedArgs.tracking.toString())
            .replace('{format}', parsedArgs.format.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::download
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:67
 * @route '/telegram/tracking/{tracking}/export/{format}'
 */
download.get = (args: { tracking: string | number, format: string | number } | [tracking: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::download
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:67
 * @route '/telegram/tracking/{tracking}/export/{format}'
 */
download.head = (args: { tracking: string | number, format: string | number } | [tracking: string | number, format: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::download
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:67
 * @route '/telegram/tracking/{tracking}/export/{format}'
 */
    const downloadForm = (args: { tracking: string | number, format: string | number } | [tracking: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: download.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::download
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:67
 * @route '/telegram/tracking/{tracking}/export/{format}'
 */
        downloadForm.get = (args: { tracking: string | number, format: string | number } | [tracking: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: download.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Telegram\TelegramTrackingController::download
 * @see app/Http/Controllers/Telegram/TelegramTrackingController.php:67
 * @route '/telegram/tracking/{tracking}/export/{format}'
 */
        downloadForm.head = (args: { tracking: string | number, format: string | number } | [tracking: string | number, format: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: download.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    download.form = downloadForm
const tracking = {
    index: Object.assign(index, index),
validate: Object.assign(validate, validate),
store: Object.assign(store, store),
change: Object.assign(change, change),
messages: Object.assign(messages, messages),
download: Object.assign(download, download),
}

export default tracking