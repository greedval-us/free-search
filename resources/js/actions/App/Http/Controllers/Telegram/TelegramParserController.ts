import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::start
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:26
 * @route '/telegram/parser/start'
 */
export const start = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(options),
    method: 'post',
})

start.definition = {
    methods: ["post"],
    url: '/telegram/parser/start',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::start
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:26
 * @route '/telegram/parser/start'
 */
start.url = (options?: RouteQueryOptions) => {
    return start.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::start
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:26
 * @route '/telegram/parser/start'
 */
start.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramParserController::start
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:26
 * @route '/telegram/parser/start'
 */
    const startForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: start.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramParserController::start
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:26
 * @route '/telegram/parser/start'
 */
        startForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: start.url(options),
            method: 'post',
        })
    
    start.form = startForm
/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::status
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:38
 * @route '/telegram/parser/status/{runId}'
 */
export const status = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

status.definition = {
    methods: ["get","head"],
    url: '/telegram/parser/status/{runId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::status
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:38
 * @route '/telegram/parser/status/{runId}'
 */
status.url = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { runId: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    runId: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        runId: args.runId,
                }

    return status.definition.url
            .replace('{runId}', parsedArgs.runId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::status
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:38
 * @route '/telegram/parser/status/{runId}'
 */
status.get = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::status
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:38
 * @route '/telegram/parser/status/{runId}'
 */
status.head = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: status.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramParserController::status
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:38
 * @route '/telegram/parser/status/{runId}'
 */
    const statusForm = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: status.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramParserController::status
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:38
 * @route '/telegram/parser/status/{runId}'
 */
        statusForm.get = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: status.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Telegram\TelegramParserController::status
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:38
 * @route '/telegram/parser/status/{runId}'
 */
        statusForm.head = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: status.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    status.form = statusForm
/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::stop
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:47
 * @route '/telegram/parser/stop/{runId}'
 */
export const stop = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stop.url(args, options),
    method: 'post',
})

stop.definition = {
    methods: ["post"],
    url: '/telegram/parser/stop/{runId}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::stop
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:47
 * @route '/telegram/parser/stop/{runId}'
 */
stop.url = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { runId: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    runId: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        runId: args.runId,
                }

    return stop.definition.url
            .replace('{runId}', parsedArgs.runId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::stop
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:47
 * @route '/telegram/parser/stop/{runId}'
 */
stop.post = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stop.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramParserController::stop
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:47
 * @route '/telegram/parser/stop/{runId}'
 */
    const stopForm = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: stop.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramParserController::stop
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:47
 * @route '/telegram/parser/stop/{runId}'
 */
        stopForm.post = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: stop.url(args, options),
            method: 'post',
        })
    
    stop.form = stopForm
/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::download
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:70
 * @route '/telegram/parser/download/{runId}'
 */
export const download = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/telegram/parser/download/{runId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::download
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:70
 * @route '/telegram/parser/download/{runId}'
 */
download.url = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { runId: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    runId: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        runId: args.runId,
                }

    return download.definition.url
            .replace('{runId}', parsedArgs.runId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::download
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:70
 * @route '/telegram/parser/download/{runId}'
 */
download.get = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Telegram\TelegramParserController::download
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:70
 * @route '/telegram/parser/download/{runId}'
 */
download.head = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Telegram\TelegramParserController::download
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:70
 * @route '/telegram/parser/download/{runId}'
 */
    const downloadForm = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: download.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Telegram\TelegramParserController::download
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:70
 * @route '/telegram/parser/download/{runId}'
 */
        downloadForm.get = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: download.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Telegram\TelegramParserController::download
 * @see app/Http/Controllers/Telegram/TelegramParserController.php:70
 * @route '/telegram/parser/download/{runId}'
 */
        downloadForm.head = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: download.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    download.form = downloadForm
const TelegramParserController = { start, status, stop, download }

export default TelegramParserController