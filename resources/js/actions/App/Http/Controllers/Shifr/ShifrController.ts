import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:24
 * @route '/shifr/hash'
 */
export const hash = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: hash.url(options),
    method: 'post',
})

hash.definition = {
    methods: ["post"],
    url: '/shifr/hash',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:24
 * @route '/shifr/hash'
 */
hash.url = (options?: RouteQueryOptions) => {
    return hash.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:24
 * @route '/shifr/hash'
 */
hash.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: hash.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:24
 * @route '/shifr/hash'
 */
    const hashForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: hash.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:24
 * @route '/shifr/hash'
 */
        hashForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: hash.url(options),
            method: 'post',
        })
    
    hash.form = hashForm
/**
* @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:32
 * @route '/shifr/transform'
 */
export const transform = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: transform.url(options),
    method: 'post',
})

transform.definition = {
    methods: ["post"],
    url: '/shifr/transform',
} satisfies RouteDefinition<["post"]>

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
transform.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: transform.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:32
 * @route '/shifr/transform'
 */
    const transformForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: transform.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:32
 * @route '/shifr/transform'
 */
        transformForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: transform.url(options),
            method: 'post',
        })
    
    transform.form = transformForm
/**
* @see \App\Http\Controllers\Shifr\ShifrController::extractIocs
 * @see app/Http/Controllers/Shifr/ShifrController.php:40
 * @route '/shifr/ioc-extract'
 */
export const extractIocs = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: extractIocs.url(options),
    method: 'post',
})

extractIocs.definition = {
    methods: ["post"],
    url: '/shifr/ioc-extract',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Shifr\ShifrController::extractIocs
 * @see app/Http/Controllers/Shifr/ShifrController.php:40
 * @route '/shifr/ioc-extract'
 */
extractIocs.url = (options?: RouteQueryOptions) => {
    return extractIocs.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shifr\ShifrController::extractIocs
 * @see app/Http/Controllers/Shifr/ShifrController.php:40
 * @route '/shifr/ioc-extract'
 */
extractIocs.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: extractIocs.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Shifr\ShifrController::extractIocs
 * @see app/Http/Controllers/Shifr/ShifrController.php:40
 * @route '/shifr/ioc-extract'
 */
    const extractIocsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: extractIocs.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Shifr\ShifrController::extractIocs
 * @see app/Http/Controllers/Shifr/ShifrController.php:40
 * @route '/shifr/ioc-extract'
 */
        extractIocsForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: extractIocs.url(options),
            method: 'post',
        })
    
    extractIocs.form = extractIocsForm
/**
* @see \App\Http\Controllers\Shifr\ShifrController::inspectJwt
 * @see app/Http/Controllers/Shifr/ShifrController.php:48
 * @route '/shifr/jwt-inspect'
 */
export const inspectJwt = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: inspectJwt.url(options),
    method: 'post',
})

inspectJwt.definition = {
    methods: ["post"],
    url: '/shifr/jwt-inspect',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Shifr\ShifrController::inspectJwt
 * @see app/Http/Controllers/Shifr/ShifrController.php:48
 * @route '/shifr/jwt-inspect'
 */
inspectJwt.url = (options?: RouteQueryOptions) => {
    return inspectJwt.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shifr\ShifrController::inspectJwt
 * @see app/Http/Controllers/Shifr/ShifrController.php:48
 * @route '/shifr/jwt-inspect'
 */
inspectJwt.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: inspectJwt.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Shifr\ShifrController::inspectJwt
 * @see app/Http/Controllers/Shifr/ShifrController.php:48
 * @route '/shifr/jwt-inspect'
 */
    const inspectJwtForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: inspectJwt.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Shifr\ShifrController::inspectJwt
 * @see app/Http/Controllers/Shifr/ShifrController.php:48
 * @route '/shifr/jwt-inspect'
 */
        inspectJwtForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: inspectJwt.url(options),
            method: 'post',
        })
    
    inspectJwt.form = inspectJwtForm
/**
* @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:56
 * @route '/shifr/classic'
 */
export const classic = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: classic.url(options),
    method: 'post',
})

classic.definition = {
    methods: ["post"],
    url: '/shifr/classic',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:56
 * @route '/shifr/classic'
 */
classic.url = (options?: RouteQueryOptions) => {
    return classic.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:56
 * @route '/shifr/classic'
 */
classic.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: classic.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:56
 * @route '/shifr/classic'
 */
    const classicForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: classic.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:56
 * @route '/shifr/classic'
 */
        classicForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: classic.url(options),
            method: 'post',
        })
    
    classic.form = classicForm
const ShifrController = { hash, transform, extractIocs, inspectJwt, classic }

export default ShifrController