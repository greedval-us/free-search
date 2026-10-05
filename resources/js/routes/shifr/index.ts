import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
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
* @see \App\Http\Controllers\Shifr\ShifrController::iocExtract
 * @see app/Http/Controllers/Shifr/ShifrController.php:40
 * @route '/shifr/ioc-extract'
 */
export const iocExtract = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: iocExtract.url(options),
    method: 'post',
})

iocExtract.definition = {
    methods: ["post"],
    url: '/shifr/ioc-extract',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Shifr\ShifrController::iocExtract
 * @see app/Http/Controllers/Shifr/ShifrController.php:40
 * @route '/shifr/ioc-extract'
 */
iocExtract.url = (options?: RouteQueryOptions) => {
    return iocExtract.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shifr\ShifrController::iocExtract
 * @see app/Http/Controllers/Shifr/ShifrController.php:40
 * @route '/shifr/ioc-extract'
 */
iocExtract.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: iocExtract.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Shifr\ShifrController::iocExtract
 * @see app/Http/Controllers/Shifr/ShifrController.php:40
 * @route '/shifr/ioc-extract'
 */
    const iocExtractForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: iocExtract.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Shifr\ShifrController::iocExtract
 * @see app/Http/Controllers/Shifr/ShifrController.php:40
 * @route '/shifr/ioc-extract'
 */
        iocExtractForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: iocExtract.url(options),
            method: 'post',
        })
    
    iocExtract.form = iocExtractForm
/**
* @see \App\Http\Controllers\Shifr\ShifrController::jwtInspect
 * @see app/Http/Controllers/Shifr/ShifrController.php:48
 * @route '/shifr/jwt-inspect'
 */
export const jwtInspect = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: jwtInspect.url(options),
    method: 'post',
})

jwtInspect.definition = {
    methods: ["post"],
    url: '/shifr/jwt-inspect',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Shifr\ShifrController::jwtInspect
 * @see app/Http/Controllers/Shifr/ShifrController.php:48
 * @route '/shifr/jwt-inspect'
 */
jwtInspect.url = (options?: RouteQueryOptions) => {
    return jwtInspect.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shifr\ShifrController::jwtInspect
 * @see app/Http/Controllers/Shifr/ShifrController.php:48
 * @route '/shifr/jwt-inspect'
 */
jwtInspect.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: jwtInspect.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Shifr\ShifrController::jwtInspect
 * @see app/Http/Controllers/Shifr/ShifrController.php:48
 * @route '/shifr/jwt-inspect'
 */
    const jwtInspectForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: jwtInspect.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Shifr\ShifrController::jwtInspect
 * @see app/Http/Controllers/Shifr/ShifrController.php:48
 * @route '/shifr/jwt-inspect'
 */
        jwtInspectForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: jwtInspect.url(options),
            method: 'post',
        })
    
    jwtInspect.form = jwtInspectForm
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
const shifr = {
    hash: Object.assign(hash, hash),
transform: Object.assign(transform, transform),
iocExtract: Object.assign(iocExtract, iocExtract),
jwtInspect: Object.assign(jwtInspect, jwtInspect),
classic: Object.assign(classic, classic),
}

export default shifr