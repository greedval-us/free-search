import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:23
 * @route '/shifr/hash'
 */
export const hash = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: hash.url(options),
    method: 'get',
})

hash.definition = {
    methods: ["get","head"],
    url: '/shifr/hash',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:23
 * @route '/shifr/hash'
 */
hash.url = (options?: RouteQueryOptions) => {
    return hash.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:23
 * @route '/shifr/hash'
 */
hash.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: hash.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:23
 * @route '/shifr/hash'
 */
hash.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: hash.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:23
 * @route '/shifr/hash'
 */
    const hashForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: hash.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:23
 * @route '/shifr/hash'
 */
        hashForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: hash.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:23
 * @route '/shifr/hash'
 */
        hashForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: hash.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    hash.form = hashForm
/**
* @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:32
 * @route '/shifr/transform'
 */
export const transform = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transform.url(options),
    method: 'get',
})

transform.definition = {
    methods: ["get","head"],
    url: '/shifr/transform',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:32
 * @route '/shifr/transform'
 */
transform.url = (options?: RouteQueryOptions) => {
    return transform.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:32
 * @route '/shifr/transform'
 */
transform.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transform.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:32
 * @route '/shifr/transform'
 */
transform.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: transform.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:32
 * @route '/shifr/transform'
 */
    const transformForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: transform.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:32
 * @route '/shifr/transform'
 */
        transformForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: transform.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:32
 * @route '/shifr/transform'
 */
        transformForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: transform.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    transform.form = transformForm
/**
* @see \App\Http\Controllers\Shifr\ShifrController::extractIocs
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
export const extractIocs = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: extractIocs.url(options),
    method: 'get',
})

extractIocs.definition = {
    methods: ["get","head"],
    url: '/shifr/ioc-extract',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shifr\ShifrController::extractIocs
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
extractIocs.url = (options?: RouteQueryOptions) => {
    return extractIocs.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shifr\ShifrController::extractIocs
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
extractIocs.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: extractIocs.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Shifr\ShifrController::extractIocs
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
extractIocs.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: extractIocs.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Shifr\ShifrController::extractIocs
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
    const extractIocsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: extractIocs.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Shifr\ShifrController::extractIocs
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
        extractIocsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: extractIocs.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Shifr\ShifrController::extractIocs
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
        extractIocsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: extractIocs.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    extractIocs.form = extractIocsForm
/**
* @see \App\Http\Controllers\Shifr\ShifrController::inspectJwt
 * @see app/Http/Controllers/Shifr/ShifrController.php:50
 * @route '/shifr/jwt-inspect'
 */
export const inspectJwt = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: inspectJwt.url(options),
    method: 'get',
})

inspectJwt.definition = {
    methods: ["get","head"],
    url: '/shifr/jwt-inspect',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shifr\ShifrController::inspectJwt
 * @see app/Http/Controllers/Shifr/ShifrController.php:50
 * @route '/shifr/jwt-inspect'
 */
inspectJwt.url = (options?: RouteQueryOptions) => {
    return inspectJwt.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shifr\ShifrController::inspectJwt
 * @see app/Http/Controllers/Shifr/ShifrController.php:50
 * @route '/shifr/jwt-inspect'
 */
inspectJwt.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: inspectJwt.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Shifr\ShifrController::inspectJwt
 * @see app/Http/Controllers/Shifr/ShifrController.php:50
 * @route '/shifr/jwt-inspect'
 */
inspectJwt.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: inspectJwt.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Shifr\ShifrController::inspectJwt
 * @see app/Http/Controllers/Shifr/ShifrController.php:50
 * @route '/shifr/jwt-inspect'
 */
    const inspectJwtForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: inspectJwt.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Shifr\ShifrController::inspectJwt
 * @see app/Http/Controllers/Shifr/ShifrController.php:50
 * @route '/shifr/jwt-inspect'
 */
        inspectJwtForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: inspectJwt.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Shifr\ShifrController::inspectJwt
 * @see app/Http/Controllers/Shifr/ShifrController.php:50
 * @route '/shifr/jwt-inspect'
 */
        inspectJwtForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: inspectJwt.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    inspectJwt.form = inspectJwtForm
/**
* @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:59
 * @route '/shifr/classic'
 */
export const classic = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: classic.url(options),
    method: 'get',
})

classic.definition = {
    methods: ["get","head"],
    url: '/shifr/classic',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:59
 * @route '/shifr/classic'
 */
classic.url = (options?: RouteQueryOptions) => {
    return classic.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:59
 * @route '/shifr/classic'
 */
classic.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: classic.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:59
 * @route '/shifr/classic'
 */
classic.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: classic.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:59
 * @route '/shifr/classic'
 */
    const classicForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: classic.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:59
 * @route '/shifr/classic'
 */
        classicForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: classic.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:59
 * @route '/shifr/classic'
 */
        classicForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: classic.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    classic.form = classicForm
const ShifrController = { hash, transform, extractIocs, inspectJwt, classic }

export default ShifrController