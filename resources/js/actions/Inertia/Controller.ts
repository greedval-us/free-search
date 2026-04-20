import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost'
 */
const Controllerf6d964f3e6b79a781fcc576ed0228a8b = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerf6d964f3e6b79a781fcc576ed0228a8b.url(options),
    method: 'get',
})

Controllerf6d964f3e6b79a781fcc576ed0228a8b.definition = {
    methods: ["get","head"],
    url: '/localhost',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost'
 */
Controllerf6d964f3e6b79a781fcc576ed0228a8b.url = (options?: RouteQueryOptions) => {
    return Controllerf6d964f3e6b79a781fcc576ed0228a8b.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost'
 */
Controllerf6d964f3e6b79a781fcc576ed0228a8b.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerf6d964f3e6b79a781fcc576ed0228a8b.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost'
 */
Controllerf6d964f3e6b79a781fcc576ed0228a8b.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllerf6d964f3e6b79a781fcc576ed0228a8b.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost'
 */
    const Controllerf6d964f3e6b79a781fcc576ed0228a8bForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controllerf6d964f3e6b79a781fcc576ed0228a8b.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost'
 */
        Controllerf6d964f3e6b79a781fcc576ed0228a8bForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllerf6d964f3e6b79a781fcc576ed0228a8b.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost'
 */
        Controllerf6d964f3e6b79a781fcc576ed0228a8bForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllerf6d964f3e6b79a781fcc576ed0228a8b.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controllerf6d964f3e6b79a781fcc576ed0228a8b.form = Controllerf6d964f3e6b79a781fcc576ed0228a8bForm
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/dashboard'
 */
const Controller82954eec63fa1747224414bbb8556e36 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller82954eec63fa1747224414bbb8556e36.url(options),
    method: 'get',
})

Controller82954eec63fa1747224414bbb8556e36.definition = {
    methods: ["get","head"],
    url: '/localhost/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/dashboard'
 */
Controller82954eec63fa1747224414bbb8556e36.url = (options?: RouteQueryOptions) => {
    return Controller82954eec63fa1747224414bbb8556e36.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/dashboard'
 */
Controller82954eec63fa1747224414bbb8556e36.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller82954eec63fa1747224414bbb8556e36.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/dashboard'
 */
Controller82954eec63fa1747224414bbb8556e36.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller82954eec63fa1747224414bbb8556e36.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/dashboard'
 */
    const Controller82954eec63fa1747224414bbb8556e36Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controller82954eec63fa1747224414bbb8556e36.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/dashboard'
 */
        Controller82954eec63fa1747224414bbb8556e36Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller82954eec63fa1747224414bbb8556e36.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/dashboard'
 */
        Controller82954eec63fa1747224414bbb8556e36Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller82954eec63fa1747224414bbb8556e36.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controller82954eec63fa1747224414bbb8556e36.form = Controller82954eec63fa1747224414bbb8556e36Form
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/telegram'
 */
const Controllerbaac2b5c001554e3856e15bf4bc42ab0 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerbaac2b5c001554e3856e15bf4bc42ab0.url(options),
    method: 'get',
})

Controllerbaac2b5c001554e3856e15bf4bc42ab0.definition = {
    methods: ["get","head"],
    url: '/localhost/telegram',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/telegram'
 */
Controllerbaac2b5c001554e3856e15bf4bc42ab0.url = (options?: RouteQueryOptions) => {
    return Controllerbaac2b5c001554e3856e15bf4bc42ab0.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/telegram'
 */
Controllerbaac2b5c001554e3856e15bf4bc42ab0.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerbaac2b5c001554e3856e15bf4bc42ab0.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/telegram'
 */
Controllerbaac2b5c001554e3856e15bf4bc42ab0.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllerbaac2b5c001554e3856e15bf4bc42ab0.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/telegram'
 */
    const Controllerbaac2b5c001554e3856e15bf4bc42ab0Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controllerbaac2b5c001554e3856e15bf4bc42ab0.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/telegram'
 */
        Controllerbaac2b5c001554e3856e15bf4bc42ab0Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllerbaac2b5c001554e3856e15bf4bc42ab0.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/telegram'
 */
        Controllerbaac2b5c001554e3856e15bf4bc42ab0Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllerbaac2b5c001554e3856e15bf4bc42ab0.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controllerbaac2b5c001554e3856e15bf4bc42ab0.form = Controllerbaac2b5c001554e3856e15bf4bc42ab0Form
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/username'
 */
const Controller541af7f31b9b62128fd44029476293af = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller541af7f31b9b62128fd44029476293af.url(options),
    method: 'get',
})

Controller541af7f31b9b62128fd44029476293af.definition = {
    methods: ["get","head"],
    url: '/localhost/username',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/username'
 */
Controller541af7f31b9b62128fd44029476293af.url = (options?: RouteQueryOptions) => {
    return Controller541af7f31b9b62128fd44029476293af.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/username'
 */
Controller541af7f31b9b62128fd44029476293af.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller541af7f31b9b62128fd44029476293af.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/username'
 */
Controller541af7f31b9b62128fd44029476293af.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller541af7f31b9b62128fd44029476293af.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/username'
 */
    const Controller541af7f31b9b62128fd44029476293afForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controller541af7f31b9b62128fd44029476293af.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/username'
 */
        Controller541af7f31b9b62128fd44029476293afForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller541af7f31b9b62128fd44029476293af.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/username'
 */
        Controller541af7f31b9b62128fd44029476293afForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller541af7f31b9b62128fd44029476293af.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controller541af7f31b9b62128fd44029476293af.form = Controller541af7f31b9b62128fd44029476293afForm
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/settings/appearance'
 */
const Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f.url(options),
    method: 'get',
})

Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f.definition = {
    methods: ["get","head"],
    url: '/localhost/settings/appearance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/settings/appearance'
 */
Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f.url = (options?: RouteQueryOptions) => {
    return Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/settings/appearance'
 */
Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/settings/appearance'
 */
Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/settings/appearance'
 */
    const Controller6f1e3af33d53afdd9ff1fd9ba9c53e8fForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/settings/appearance'
 */
        Controller6f1e3af33d53afdd9ff1fd9ba9c53e8fForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/localhost/settings/appearance'
 */
        Controller6f1e3af33d53afdd9ff1fd9ba9c53e8fForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f.form = Controller6f1e3af33d53afdd9ff1fd9ba9c53e8fForm

const Controller = {
    '/localhost': Controllerf6d964f3e6b79a781fcc576ed0228a8b,
    '/localhost/dashboard': Controller82954eec63fa1747224414bbb8556e36,
    '/localhost/telegram': Controllerbaac2b5c001554e3856e15bf4bc42ab0,
    '/localhost/username': Controller541af7f31b9b62128fd44029476293af,
    '/localhost/settings/appearance': Controller6f1e3af33d53afdd9ff1fd9ba9c53e8f,
}

export default Controller