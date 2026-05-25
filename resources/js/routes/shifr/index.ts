import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:25
 * @route '/shifr/hash'
 */
export const hash = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: hash.url(options),
    method: 'get',
});

hash.definition = {
    methods: ['get', 'head'],
    url: '/shifr/hash',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:25
 * @route '/shifr/hash'
 */
hash.url = (options?: RouteQueryOptions) => {
    return hash.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:25
 * @route '/shifr/hash'
 */
hash.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: hash.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:25
 * @route '/shifr/hash'
 */
hash.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: hash.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:25
 * @route '/shifr/hash'
 */
const hashForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: hash.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:25
 * @route '/shifr/hash'
 */
hashForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: hash.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::hash
 * @see app/Http/Controllers/Shifr/ShifrController.php:25
 * @route '/shifr/hash'
 */
hashForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: hash.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

hash.form = hashForm;
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:33
 * @route '/shifr/transform'
 */
export const transform = (
    options?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: transform.url(options),
    method: 'get',
});

transform.definition = {
    methods: ['get', 'head'],
    url: '/shifr/transform',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:33
 * @route '/shifr/transform'
 */
transform.url = (options?: RouteQueryOptions) => {
    return transform.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:33
 * @route '/shifr/transform'
 */
transform.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transform.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:33
 * @route '/shifr/transform'
 */
transform.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: transform.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:33
 * @route '/shifr/transform'
 */
const transformForm = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: transform.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:33
 * @route '/shifr/transform'
 */
transformForm.get = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: transform.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::transform
 * @see app/Http/Controllers/Shifr/ShifrController.php:33
 * @route '/shifr/transform'
 */
transformForm.head = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: transform.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

transform.form = transformForm;
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::iocExtract
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
export const iocExtract = (
    options?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: iocExtract.url(options),
    method: 'get',
});

iocExtract.definition = {
    methods: ['get', 'head'],
    url: '/shifr/ioc-extract',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::iocExtract
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
iocExtract.url = (options?: RouteQueryOptions) => {
    return iocExtract.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::iocExtract
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
iocExtract.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: iocExtract.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::iocExtract
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
iocExtract.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: iocExtract.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::iocExtract
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
const iocExtractForm = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: iocExtract.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::iocExtract
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
iocExtractForm.get = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: iocExtract.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::iocExtract
 * @see app/Http/Controllers/Shifr/ShifrController.php:41
 * @route '/shifr/ioc-extract'
 */
iocExtractForm.head = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: iocExtract.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

iocExtract.form = iocExtractForm;
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::jwtInspect
 * @see app/Http/Controllers/Shifr/ShifrController.php:49
 * @route '/shifr/jwt-inspect'
 */
export const jwtInspect = (
    options?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: jwtInspect.url(options),
    method: 'get',
});

jwtInspect.definition = {
    methods: ['get', 'head'],
    url: '/shifr/jwt-inspect',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::jwtInspect
 * @see app/Http/Controllers/Shifr/ShifrController.php:49
 * @route '/shifr/jwt-inspect'
 */
jwtInspect.url = (options?: RouteQueryOptions) => {
    return jwtInspect.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::jwtInspect
 * @see app/Http/Controllers/Shifr/ShifrController.php:49
 * @route '/shifr/jwt-inspect'
 */
jwtInspect.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: jwtInspect.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::jwtInspect
 * @see app/Http/Controllers/Shifr/ShifrController.php:49
 * @route '/shifr/jwt-inspect'
 */
jwtInspect.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: jwtInspect.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::jwtInspect
 * @see app/Http/Controllers/Shifr/ShifrController.php:49
 * @route '/shifr/jwt-inspect'
 */
const jwtInspectForm = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: jwtInspect.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::jwtInspect
 * @see app/Http/Controllers/Shifr/ShifrController.php:49
 * @route '/shifr/jwt-inspect'
 */
jwtInspectForm.get = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: jwtInspect.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::jwtInspect
 * @see app/Http/Controllers/Shifr/ShifrController.php:49
 * @route '/shifr/jwt-inspect'
 */
jwtInspectForm.head = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: jwtInspect.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

jwtInspect.form = jwtInspectForm;
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:57
 * @route '/shifr/classic'
 */
export const classic = (
    options?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: classic.url(options),
    method: 'get',
});

classic.definition = {
    methods: ['get', 'head'],
    url: '/shifr/classic',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:57
 * @route '/shifr/classic'
 */
classic.url = (options?: RouteQueryOptions) => {
    return classic.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:57
 * @route '/shifr/classic'
 */
classic.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: classic.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:57
 * @route '/shifr/classic'
 */
classic.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: classic.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:57
 * @route '/shifr/classic'
 */
const classicForm = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: classic.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:57
 * @route '/shifr/classic'
 */
classicForm.get = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: classic.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Shifr\ShifrController::classic
 * @see app/Http/Controllers/Shifr/ShifrController.php:57
 * @route '/shifr/classic'
 */
classicForm.head = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: classic.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

classic.form = classicForm;
const shifr = {
    hash: Object.assign(hash, hash),
    transform: Object.assign(transform, transform),
    iocExtract: Object.assign(iocExtract, iocExtract),
    jwtInspect: Object.assign(jwtInspect, jwtInspect),
    classic: Object.assign(classic, classic),
};

export default shifr;
