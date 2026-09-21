import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions'
 */
const TelegramSessionController3aaf5e227d31529605b3c22bff57f938 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: TelegramSessionController3aaf5e227d31529605b3c22bff57f938.url(options),
    method: 'post',
})

TelegramSessionController3aaf5e227d31529605b3c22bff57f938.definition = {
    methods: ["post"],
    url: '/admin/telegram-sessions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions'
 */
TelegramSessionController3aaf5e227d31529605b3c22bff57f938.url = (options?: RouteQueryOptions) => {
    return TelegramSessionController3aaf5e227d31529605b3c22bff57f938.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions'
 */
TelegramSessionController3aaf5e227d31529605b3c22bff57f938.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: TelegramSessionController3aaf5e227d31529605b3c22bff57f938.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions'
 */
    const TelegramSessionController3aaf5e227d31529605b3c22bff57f938Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: TelegramSessionController3aaf5e227d31529605b3c22bff57f938.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions'
 */
        TelegramSessionController3aaf5e227d31529605b3c22bff57f938Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: TelegramSessionController3aaf5e227d31529605b3c22bff57f938.url(options),
            method: 'post',
        })
    
    TelegramSessionController3aaf5e227d31529605b3c22bff57f938.form = TelegramSessionController3aaf5e227d31529605b3c22bff57f938Form
    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/phone'
 */
const TelegramSessionController719ea18359c64365b7f9a5bf7999ed54 = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: TelegramSessionController719ea18359c64365b7f9a5bf7999ed54.url(args, options),
    method: 'post',
})

TelegramSessionController719ea18359c64365b7f9a5bf7999ed54.definition = {
    methods: ["post"],
    url: '/admin/telegram-sessions/{connection}/phone',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/phone'
 */
TelegramSessionController719ea18359c64365b7f9a5bf7999ed54.url = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { connection: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    connection: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        connection: args.connection,
                }

    return TelegramSessionController719ea18359c64365b7f9a5bf7999ed54.definition.url
            .replace('{connection}', parsedArgs.connection.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/phone'
 */
TelegramSessionController719ea18359c64365b7f9a5bf7999ed54.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: TelegramSessionController719ea18359c64365b7f9a5bf7999ed54.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/phone'
 */
    const TelegramSessionController719ea18359c64365b7f9a5bf7999ed54Form = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: TelegramSessionController719ea18359c64365b7f9a5bf7999ed54.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/phone'
 */
        TelegramSessionController719ea18359c64365b7f9a5bf7999ed54Form.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: TelegramSessionController719ea18359c64365b7f9a5bf7999ed54.url(args, options),
            method: 'post',
        })
    
    TelegramSessionController719ea18359c64365b7f9a5bf7999ed54.form = TelegramSessionController719ea18359c64365b7f9a5bf7999ed54Form
    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/code'
 */
const TelegramSessionControllerf651a65f2fc0d948b64605704f310eee = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: TelegramSessionControllerf651a65f2fc0d948b64605704f310eee.url(args, options),
    method: 'post',
})

TelegramSessionControllerf651a65f2fc0d948b64605704f310eee.definition = {
    methods: ["post"],
    url: '/admin/telegram-sessions/{connection}/code',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/code'
 */
TelegramSessionControllerf651a65f2fc0d948b64605704f310eee.url = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { connection: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    connection: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        connection: args.connection,
                }

    return TelegramSessionControllerf651a65f2fc0d948b64605704f310eee.definition.url
            .replace('{connection}', parsedArgs.connection.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/code'
 */
TelegramSessionControllerf651a65f2fc0d948b64605704f310eee.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: TelegramSessionControllerf651a65f2fc0d948b64605704f310eee.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/code'
 */
    const TelegramSessionControllerf651a65f2fc0d948b64605704f310eeeForm = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: TelegramSessionControllerf651a65f2fc0d948b64605704f310eee.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/code'
 */
        TelegramSessionControllerf651a65f2fc0d948b64605704f310eeeForm.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: TelegramSessionControllerf651a65f2fc0d948b64605704f310eee.url(args, options),
            method: 'post',
        })
    
    TelegramSessionControllerf651a65f2fc0d948b64605704f310eee.form = TelegramSessionControllerf651a65f2fc0d948b64605704f310eeeForm
    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/password'
 */
const TelegramSessionController6c146de441f35a1340eb14dde0f6ac52 = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: TelegramSessionController6c146de441f35a1340eb14dde0f6ac52.url(args, options),
    method: 'post',
})

TelegramSessionController6c146de441f35a1340eb14dde0f6ac52.definition = {
    methods: ["post"],
    url: '/admin/telegram-sessions/{connection}/password',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/password'
 */
TelegramSessionController6c146de441f35a1340eb14dde0f6ac52.url = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { connection: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    connection: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        connection: args.connection,
                }

    return TelegramSessionController6c146de441f35a1340eb14dde0f6ac52.definition.url
            .replace('{connection}', parsedArgs.connection.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/password'
 */
TelegramSessionController6c146de441f35a1340eb14dde0f6ac52.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: TelegramSessionController6c146de441f35a1340eb14dde0f6ac52.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/password'
 */
    const TelegramSessionController6c146de441f35a1340eb14dde0f6ac52Form = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: TelegramSessionController6c146de441f35a1340eb14dde0f6ac52.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/password'
 */
        TelegramSessionController6c146de441f35a1340eb14dde0f6ac52Form.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: TelegramSessionController6c146de441f35a1340eb14dde0f6ac52.url(args, options),
            method: 'post',
        })
    
    TelegramSessionController6c146de441f35a1340eb14dde0f6ac52.form = TelegramSessionController6c146de441f35a1340eb14dde0f6ac52Form
    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/inspect'
 */
const TelegramSessionControllera8a4d8c11419d9c348523c69ddc237ca = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: TelegramSessionControllera8a4d8c11419d9c348523c69ddc237ca.url(args, options),
    method: 'post',
})

TelegramSessionControllera8a4d8c11419d9c348523c69ddc237ca.definition = {
    methods: ["post"],
    url: '/admin/telegram-sessions/{connection}/inspect',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/inspect'
 */
TelegramSessionControllera8a4d8c11419d9c348523c69ddc237ca.url = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { connection: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    connection: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        connection: args.connection,
                }

    return TelegramSessionControllera8a4d8c11419d9c348523c69ddc237ca.definition.url
            .replace('{connection}', parsedArgs.connection.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/inspect'
 */
TelegramSessionControllera8a4d8c11419d9c348523c69ddc237ca.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: TelegramSessionControllera8a4d8c11419d9c348523c69ddc237ca.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/inspect'
 */
    const TelegramSessionControllera8a4d8c11419d9c348523c69ddc237caForm = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: TelegramSessionControllera8a4d8c11419d9c348523c69ddc237ca.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/inspect'
 */
        TelegramSessionControllera8a4d8c11419d9c348523c69ddc237caForm.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: TelegramSessionControllera8a4d8c11419d9c348523c69ddc237ca.url(args, options),
            method: 'post',
        })
    
    TelegramSessionControllera8a4d8c11419d9c348523c69ddc237ca.form = TelegramSessionControllera8a4d8c11419d9c348523c69ddc237caForm

/**
* Multiple routes resolve to \App\Http\Controllers\MoonShine\TelegramSessionController::TelegramSessionController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `TelegramSessionController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const TelegramSessionController = {
    '/admin/telegram-sessions': TelegramSessionController3aaf5e227d31529605b3c22bff57f938,
    '/admin/telegram-sessions/{connection}/phone': TelegramSessionController719ea18359c64365b7f9a5bf7999ed54,
    '/admin/telegram-sessions/{connection}/code': TelegramSessionControllerf651a65f2fc0d948b64605704f310eee,
    '/admin/telegram-sessions/{connection}/password': TelegramSessionController6c146de441f35a1340eb14dde0f6ac52,
    '/admin/telegram-sessions/{connection}/inspect': TelegramSessionControllera8a4d8c11419d9c348523c69ddc237ca,
}

export default TelegramSessionController