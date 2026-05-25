import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../../../wayfinder';
/**
 * @see \App\Http\Controllers\CompanyIntel\CompanyIntelController::lookup
 * @see app/Http/Controllers/CompanyIntel/CompanyIntelController.php:17
 * @route '/company-intel/lookup'
 */
export const lookup = (
    options?: RouteQueryOptions
): RouteDefinition<'get'> => ({
    url: lookup.url(options),
    method: 'get',
});

lookup.definition = {
    methods: ['get', 'head'],
    url: '/company-intel/lookup',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\CompanyIntel\CompanyIntelController::lookup
 * @see app/Http/Controllers/CompanyIntel/CompanyIntelController.php:17
 * @route '/company-intel/lookup'
 */
lookup.url = (options?: RouteQueryOptions) => {
    return lookup.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\CompanyIntel\CompanyIntelController::lookup
 * @see app/Http/Controllers/CompanyIntel/CompanyIntelController.php:17
 * @route '/company-intel/lookup'
 */
lookup.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: lookup.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\CompanyIntel\CompanyIntelController::lookup
 * @see app/Http/Controllers/CompanyIntel/CompanyIntelController.php:17
 * @route '/company-intel/lookup'
 */
lookup.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: lookup.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\CompanyIntel\CompanyIntelController::lookup
 * @see app/Http/Controllers/CompanyIntel/CompanyIntelController.php:17
 * @route '/company-intel/lookup'
 */
const lookupForm = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: lookup.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\CompanyIntel\CompanyIntelController::lookup
 * @see app/Http/Controllers/CompanyIntel/CompanyIntelController.php:17
 * @route '/company-intel/lookup'
 */
lookupForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: lookup.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\CompanyIntel\CompanyIntelController::lookup
 * @see app/Http/Controllers/CompanyIntel/CompanyIntelController.php:17
 * @route '/company-intel/lookup'
 */
lookupForm.head = (
    options?: RouteQueryOptions
): RouteFormDefinition<'get'> => ({
    action: lookup.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

lookup.form = lookupForm;
const CompanyIntelController = { lookup };

export default CompanyIntelController;
