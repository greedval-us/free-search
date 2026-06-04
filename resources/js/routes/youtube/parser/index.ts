import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::comments
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:33
 * @route '/youtube/parser/comments'
 */
export const comments = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: comments.url(options),
    method: 'get',
})

comments.definition = {
    methods: ["get","head"],
    url: '/youtube/parser/comments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::comments
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:33
 * @route '/youtube/parser/comments'
 */
comments.url = (options?: RouteQueryOptions) => {
    return comments.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::comments
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:33
 * @route '/youtube/parser/comments'
 */
comments.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: comments.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::comments
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:33
 * @route '/youtube/parser/comments'
 */
comments.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: comments.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::comments
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:33
 * @route '/youtube/parser/comments'
 */
    const commentsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: comments.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::comments
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:33
 * @route '/youtube/parser/comments'
 */
        commentsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: comments.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::comments
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:33
 * @route '/youtube/parser/comments'
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
/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::start
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:52
 * @route '/youtube/parser/start'
 */
export const start = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(options),
    method: 'post',
})

start.definition = {
    methods: ["post"],
    url: '/youtube/parser/start',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::start
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:52
 * @route '/youtube/parser/start'
 */
start.url = (options?: RouteQueryOptions) => {
    return start.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::start
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:52
 * @route '/youtube/parser/start'
 */
start.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::start
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:52
 * @route '/youtube/parser/start'
 */
    const startForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: start.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::start
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:52
 * @route '/youtube/parser/start'
 */
        startForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: start.url(options),
            method: 'post',
        })
    
    start.form = startForm
/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::status
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:57
 * @route '/youtube/parser/status/{runId}'
 */
export const status = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

status.definition = {
    methods: ["get","head"],
    url: '/youtube/parser/status/{runId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::status
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:57
 * @route '/youtube/parser/status/{runId}'
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
* @see \App\Http\Controllers\YouTube\YouTubeParserController::status
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:57
 * @route '/youtube/parser/status/{runId}'
 */
status.get = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::status
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:57
 * @route '/youtube/parser/status/{runId}'
 */
status.head = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: status.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::status
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:57
 * @route '/youtube/parser/status/{runId}'
 */
    const statusForm = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: status.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::status
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:57
 * @route '/youtube/parser/status/{runId}'
 */
        statusForm.get = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: status.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::status
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:57
 * @route '/youtube/parser/status/{runId}'
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
* @see \App\Http\Controllers\YouTube\YouTubeParserController::stop
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:64
 * @route '/youtube/parser/stop/{runId}'
 */
export const stop = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stop.url(args, options),
    method: 'post',
})

stop.definition = {
    methods: ["post"],
    url: '/youtube/parser/stop/{runId}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::stop
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:64
 * @route '/youtube/parser/stop/{runId}'
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
* @see \App\Http\Controllers\YouTube\YouTubeParserController::stop
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:64
 * @route '/youtube/parser/stop/{runId}'
 */
stop.post = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stop.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::stop
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:64
 * @route '/youtube/parser/stop/{runId}'
 */
    const stopForm = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: stop.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::stop
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:64
 * @route '/youtube/parser/stop/{runId}'
 */
        stopForm.post = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: stop.url(args, options),
            method: 'post',
        })
    
    stop.form = stopForm
/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadExcel
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:71
 * @route '/youtube/parser/download-excel/{runId}'
 */
export const downloadExcel = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadExcel.url(args, options),
    method: 'get',
})

downloadExcel.definition = {
    methods: ["get","head"],
    url: '/youtube/parser/download-excel/{runId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadExcel
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:71
 * @route '/youtube/parser/download-excel/{runId}'
 */
downloadExcel.url = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return downloadExcel.definition.url
            .replace('{runId}', parsedArgs.runId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadExcel
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:71
 * @route '/youtube/parser/download-excel/{runId}'
 */
downloadExcel.get = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadExcel.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadExcel
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:71
 * @route '/youtube/parser/download-excel/{runId}'
 */
downloadExcel.head = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: downloadExcel.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadExcel
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:71
 * @route '/youtube/parser/download-excel/{runId}'
 */
    const downloadExcelForm = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: downloadExcel.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadExcel
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:71
 * @route '/youtube/parser/download-excel/{runId}'
 */
        downloadExcelForm.get = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: downloadExcel.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadExcel
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:71
 * @route '/youtube/parser/download-excel/{runId}'
 */
        downloadExcelForm.head = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: downloadExcel.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    downloadExcel.form = downloadExcelForm
/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadJson
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:83
 * @route '/youtube/parser/download-json/{runId}'
 */
export const downloadJson = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadJson.url(args, options),
    method: 'get',
})

downloadJson.definition = {
    methods: ["get","head"],
    url: '/youtube/parser/download-json/{runId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadJson
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:83
 * @route '/youtube/parser/download-json/{runId}'
 */
downloadJson.url = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return downloadJson.definition.url
            .replace('{runId}', parsedArgs.runId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadJson
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:83
 * @route '/youtube/parser/download-json/{runId}'
 */
downloadJson.get = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadJson.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadJson
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:83
 * @route '/youtube/parser/download-json/{runId}'
 */
downloadJson.head = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: downloadJson.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadJson
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:83
 * @route '/youtube/parser/download-json/{runId}'
 */
    const downloadJsonForm = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: downloadJson.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadJson
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:83
 * @route '/youtube/parser/download-json/{runId}'
 */
        downloadJsonForm.get = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: downloadJson.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\YouTube\YouTubeParserController::downloadJson
 * @see app/Http/Controllers/YouTube/YouTubeParserController.php:83
 * @route '/youtube/parser/download-json/{runId}'
 */
        downloadJsonForm.head = (args: { runId: string | number } | [runId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: downloadJson.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    downloadJson.form = downloadJsonForm
const parser = {
    comments: Object.assign(comments, comments),
start: Object.assign(start, start),
status: Object.assign(status, status),
stop: Object.assign(stop, stop),
downloadExcel: Object.assign(downloadExcel, downloadExcel),
downloadJson: Object.assign(downloadJson, downloadJson),
}

export default parser