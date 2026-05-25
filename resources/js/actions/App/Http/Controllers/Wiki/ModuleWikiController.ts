import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../../../wayfinder';
/**
 * @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
const ModuleWikiController = (
    options?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: ModuleWikiController.url(options),
    method: 'get',
});

ModuleWikiController.definition = {
    methods: ['get', 'head'],
    url: '/wiki/modules',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
ModuleWikiController.url = (options?: RouteQueryOptions) => {
    return ModuleWikiController.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
ModuleWikiController.get = (
    options?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: ModuleWikiController.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
ModuleWikiController.head = (
    options?: RouteQueryOptions
): RouteDefinition<'head'> => ({
    url: ModuleWikiController.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
const ModuleWikiControllerForm = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: ModuleWikiController.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
ModuleWikiControllerForm.get = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: ModuleWikiController.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Wiki\ModuleWikiController::__invoke
 * @see app/Http/Controllers/Wiki/ModuleWikiController.php:11
 * @route '/wiki/modules'
 */
ModuleWikiControllerForm.head = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: ModuleWikiController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

ModuleWikiController.form = ModuleWikiControllerForm;
export default ModuleWikiController;
