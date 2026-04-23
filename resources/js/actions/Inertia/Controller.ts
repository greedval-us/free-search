import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
const Controller980bb49ee7ae63891f1d891d2fbcf1c9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})

Controller980bb49ee7ae63891f1d891d2fbcf1c9.definition = {
    methods: ["get","head"],
    url: '/',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
Controller980bb49ee7ae63891f1d891d2fbcf1c9.url = (options?: RouteQueryOptions) => {
    return Controller980bb49ee7ae63891f1d891d2fbcf1c9.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
Controller980bb49ee7ae63891f1d891d2fbcf1c9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
Controller980bb49ee7ae63891f1d891d2fbcf1c9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
    const Controller980bb49ee7ae63891f1d891d2fbcf1c9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controller980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
        Controller980bb49ee7ae63891f1d891d2fbcf1c9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
        Controller980bb49ee7ae63891f1d891d2fbcf1c9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller980bb49ee7ae63891f1d891d2fbcf1c9.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controller980bb49ee7ae63891f1d891d2fbcf1c9.form = Controller980bb49ee7ae63891f1d891d2fbcf1c9Form
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dashboard'
 */
const Controller42a740574ecbfbac32f8cc353fc32db9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller42a740574ecbfbac32f8cc353fc32db9.url(options),
    method: 'get',
})

Controller42a740574ecbfbac32f8cc353fc32db9.definition = {
    methods: ["get","head"],
    url: '/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dashboard'
 */
Controller42a740574ecbfbac32f8cc353fc32db9.url = (options?: RouteQueryOptions) => {
    return Controller42a740574ecbfbac32f8cc353fc32db9.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dashboard'
 */
Controller42a740574ecbfbac32f8cc353fc32db9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller42a740574ecbfbac32f8cc353fc32db9.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dashboard'
 */
Controller42a740574ecbfbac32f8cc353fc32db9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller42a740574ecbfbac32f8cc353fc32db9.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dashboard'
 */
    const Controller42a740574ecbfbac32f8cc353fc32db9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controller42a740574ecbfbac32f8cc353fc32db9.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dashboard'
 */
        Controller42a740574ecbfbac32f8cc353fc32db9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller42a740574ecbfbac32f8cc353fc32db9.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dashboard'
 */
        Controller42a740574ecbfbac32f8cc353fc32db9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller42a740574ecbfbac32f8cc353fc32db9.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controller42a740574ecbfbac32f8cc353fc32db9.form = Controller42a740574ecbfbac32f8cc353fc32db9Form
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/telegram'
 */
const Controllerd5ecae56bdcfbb8acbee4b606463b6bd = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerd5ecae56bdcfbb8acbee4b606463b6bd.url(options),
    method: 'get',
})

Controllerd5ecae56bdcfbb8acbee4b606463b6bd.definition = {
    methods: ["get","head"],
    url: '/telegram',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/telegram'
 */
Controllerd5ecae56bdcfbb8acbee4b606463b6bd.url = (options?: RouteQueryOptions) => {
    return Controllerd5ecae56bdcfbb8acbee4b606463b6bd.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/telegram'
 */
Controllerd5ecae56bdcfbb8acbee4b606463b6bd.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerd5ecae56bdcfbb8acbee4b606463b6bd.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/telegram'
 */
Controllerd5ecae56bdcfbb8acbee4b606463b6bd.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllerd5ecae56bdcfbb8acbee4b606463b6bd.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/telegram'
 */
    const Controllerd5ecae56bdcfbb8acbee4b606463b6bdForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controllerd5ecae56bdcfbb8acbee4b606463b6bd.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/telegram'
 */
        Controllerd5ecae56bdcfbb8acbee4b606463b6bdForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllerd5ecae56bdcfbb8acbee4b606463b6bd.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/telegram'
 */
        Controllerd5ecae56bdcfbb8acbee4b606463b6bdForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllerd5ecae56bdcfbb8acbee4b606463b6bd.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controllerd5ecae56bdcfbb8acbee4b606463b6bd.form = Controllerd5ecae56bdcfbb8acbee4b606463b6bdForm
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/username'
 */
const Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e.url(options),
    method: 'get',
})

Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e.definition = {
    methods: ["get","head"],
    url: '/username',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/username'
 */
Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e.url = (options?: RouteQueryOptions) => {
    return Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/username'
 */
Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/username'
 */
Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/username'
 */
    const Controller2454f4b0ad7800af5a3fd1b9ef9f0b2eForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/username'
 */
        Controller2454f4b0ad7800af5a3fd1b9ef9f0b2eForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/username'
 */
        Controller2454f4b0ad7800af5a3fd1b9ef9f0b2eForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e.form = Controller2454f4b0ad7800af5a3fd1b9ef9f0b2eForm
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/site-intel'
 */
const Controller8afb288f411615c9a5737c884d0340a0 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller8afb288f411615c9a5737c884d0340a0.url(options),
    method: 'get',
})

Controller8afb288f411615c9a5737c884d0340a0.definition = {
    methods: ["get","head"],
    url: '/site-intel',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/site-intel'
 */
Controller8afb288f411615c9a5737c884d0340a0.url = (options?: RouteQueryOptions) => {
    return Controller8afb288f411615c9a5737c884d0340a0.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/site-intel'
 */
Controller8afb288f411615c9a5737c884d0340a0.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller8afb288f411615c9a5737c884d0340a0.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/site-intel'
 */
Controller8afb288f411615c9a5737c884d0340a0.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller8afb288f411615c9a5737c884d0340a0.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/site-intel'
 */
    const Controller8afb288f411615c9a5737c884d0340a0Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controller8afb288f411615c9a5737c884d0340a0.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/site-intel'
 */
        Controller8afb288f411615c9a5737c884d0340a0Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller8afb288f411615c9a5737c884d0340a0.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/site-intel'
 */
        Controller8afb288f411615c9a5737c884d0340a0Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller8afb288f411615c9a5737c884d0340a0.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controller8afb288f411615c9a5737c884d0340a0.form = Controller8afb288f411615c9a5737c884d0340a0Form
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/fio'
 */
const Controller8508b634f2b8fb9774a98f0006915705 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller8508b634f2b8fb9774a98f0006915705.url(options),
    method: 'get',
})

Controller8508b634f2b8fb9774a98f0006915705.definition = {
    methods: ["get","head"],
    url: '/fio',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/fio'
 */
Controller8508b634f2b8fb9774a98f0006915705.url = (options?: RouteQueryOptions) => {
    return Controller8508b634f2b8fb9774a98f0006915705.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/fio'
 */
Controller8508b634f2b8fb9774a98f0006915705.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller8508b634f2b8fb9774a98f0006915705.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/fio'
 */
Controller8508b634f2b8fb9774a98f0006915705.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller8508b634f2b8fb9774a98f0006915705.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/fio'
 */
    const Controller8508b634f2b8fb9774a98f0006915705Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controller8508b634f2b8fb9774a98f0006915705.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/fio'
 */
        Controller8508b634f2b8fb9774a98f0006915705Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller8508b634f2b8fb9774a98f0006915705.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/fio'
 */
        Controller8508b634f2b8fb9774a98f0006915705Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller8508b634f2b8fb9774a98f0006915705.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controller8508b634f2b8fb9774a98f0006915705.form = Controller8508b634f2b8fb9774a98f0006915705Form
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dorks'
 */
const Controllercd0d66d9548f3bb8796416b11bf1776e = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllercd0d66d9548f3bb8796416b11bf1776e.url(options),
    method: 'get',
})

Controllercd0d66d9548f3bb8796416b11bf1776e.definition = {
    methods: ["get","head"],
    url: '/dorks',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dorks'
 */
Controllercd0d66d9548f3bb8796416b11bf1776e.url = (options?: RouteQueryOptions) => {
    return Controllercd0d66d9548f3bb8796416b11bf1776e.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dorks'
 */
Controllercd0d66d9548f3bb8796416b11bf1776e.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllercd0d66d9548f3bb8796416b11bf1776e.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dorks'
 */
Controllercd0d66d9548f3bb8796416b11bf1776e.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllercd0d66d9548f3bb8796416b11bf1776e.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dorks'
 */
    const Controllercd0d66d9548f3bb8796416b11bf1776eForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controllercd0d66d9548f3bb8796416b11bf1776e.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dorks'
 */
        Controllercd0d66d9548f3bb8796416b11bf1776eForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllercd0d66d9548f3bb8796416b11bf1776e.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/dorks'
 */
        Controllercd0d66d9548f3bb8796416b11bf1776eForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllercd0d66d9548f3bb8796416b11bf1776e.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controllercd0d66d9548f3bb8796416b11bf1776e.form = Controllercd0d66d9548f3bb8796416b11bf1776eForm
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
const Controllere19ee86e9cf603ce1a59a1ec5d21dec5 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})

Controllere19ee86e9cf603ce1a59a1ec5d21dec5.definition = {
    methods: ["get","head"],
    url: '/settings/appearance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url = (options?: RouteQueryOptions) => {
    return Controllere19ee86e9cf603ce1a59a1ec5d21dec5.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
Controllere19ee86e9cf603ce1a59a1ec5d21dec5.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
Controllere19ee86e9cf603ce1a59a1ec5d21dec5.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
    const Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
        Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
        Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controllere19ee86e9cf603ce1a59a1ec5d21dec5.form = Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form

const Controller = {
    '/': Controller980bb49ee7ae63891f1d891d2fbcf1c9,
    '/dashboard': Controller42a740574ecbfbac32f8cc353fc32db9,
    '/telegram': Controllerd5ecae56bdcfbb8acbee4b606463b6bd,
    '/username': Controller2454f4b0ad7800af5a3fd1b9ef9f0b2e,
    '/site-intel': Controller8afb288f411615c9a5737c884d0340a0,
    '/fio': Controller8508b634f2b8fb9774a98f0006915705,
    '/dorks': Controllercd0d66d9548f3bb8796416b11bf1776e,
    '/settings/appearance': Controllere19ee86e9cf603ce1a59a1ec5d21dec5,
}

export default Controller