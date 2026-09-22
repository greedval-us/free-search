import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/telegram-sessions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/phone'
 */
export const phone = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: phone.url(args, options),
    method: 'post',
})

phone.definition = {
    methods: ["post"],
    url: '/admin/telegram-sessions/{connection}/phone',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/phone'
 */
phone.url = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return phone.definition.url
            .replace('{connection}', parsedArgs.connection.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/phone'
 */
phone.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: phone.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/phone'
 */
    const phoneForm = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: phone.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/phone'
 */
        phoneForm.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: phone.url(args, options),
            method: 'post',
        })
    
    phone.form = phoneForm
/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/code'
 */
export const code = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: code.url(args, options),
    method: 'post',
})

code.definition = {
    methods: ["post"],
    url: '/admin/telegram-sessions/{connection}/code',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/code'
 */
code.url = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return code.definition.url
            .replace('{connection}', parsedArgs.connection.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/code'
 */
code.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: code.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/code'
 */
    const codeForm = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: code.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/code'
 */
        codeForm.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: code.url(args, options),
            method: 'post',
        })
    
    code.form = codeForm
/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/password'
 */
export const password = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: password.url(args, options),
    method: 'post',
})

password.definition = {
    methods: ["post"],
    url: '/admin/telegram-sessions/{connection}/password',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/password'
 */
password.url = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return password.definition.url
            .replace('{connection}', parsedArgs.connection.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/password'
 */
password.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: password.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/password'
 */
    const passwordForm = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: password.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/password'
 */
        passwordForm.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: password.url(args, options),
            method: 'post',
        })
    
    password.form = passwordForm
/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/inspect'
 */
export const inspect = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: inspect.url(args, options),
    method: 'post',
})

inspect.definition = {
    methods: ["post"],
    url: '/admin/telegram-sessions/{connection}/inspect',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/inspect'
 */
inspect.url = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return inspect.definition.url
            .replace('{connection}', parsedArgs.connection.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/inspect'
 */
inspect.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: inspect.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/inspect'
 */
    const inspectForm = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: inspect.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MoonShine\TelegramSessionController::__invoke
 * @see app/Http/Controllers/MoonShine/TelegramSessionController.php:18
 * @route '/admin/telegram-sessions/{connection}/inspect'
 */
        inspectForm.post = (args: { connection: string | number } | [connection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: inspect.url(args, options),
            method: 'post',
        })
    
    inspect.form = inspectForm
const telegramSessions = {
    store: Object.assign(store, store),
phone: Object.assign(phone, phone),
code: Object.assign(code, code),
password: Object.assign(password, password),
inspect: Object.assign(inspect, inspect),
}

export default telegramSessions