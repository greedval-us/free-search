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
 * @route '/news-media-intel'
 */
const Controller36a2b357ae6134bc8055dfa5be128cd4 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller36a2b357ae6134bc8055dfa5be128cd4.url(options),
    method: 'get',
})

Controller36a2b357ae6134bc8055dfa5be128cd4.definition = {
    methods: ["get","head"],
    url: '/news-media-intel',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/news-media-intel'
 */
Controller36a2b357ae6134bc8055dfa5be128cd4.url = (options?: RouteQueryOptions) => {
    return Controller36a2b357ae6134bc8055dfa5be128cd4.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/news-media-intel'
 */
Controller36a2b357ae6134bc8055dfa5be128cd4.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller36a2b357ae6134bc8055dfa5be128cd4.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/news-media-intel'
 */
Controller36a2b357ae6134bc8055dfa5be128cd4.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller36a2b357ae6134bc8055dfa5be128cd4.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/news-media-intel'
 */
    const Controller36a2b357ae6134bc8055dfa5be128cd4Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controller36a2b357ae6134bc8055dfa5be128cd4.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/news-media-intel'
 */
        Controller36a2b357ae6134bc8055dfa5be128cd4Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller36a2b357ae6134bc8055dfa5be128cd4.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/news-media-intel'
 */
        Controller36a2b357ae6134bc8055dfa5be128cd4Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller36a2b357ae6134bc8055dfa5be128cd4.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controller36a2b357ae6134bc8055dfa5be128cd4.form = Controller36a2b357ae6134bc8055dfa5be128cd4Form
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/shifr'
 */
const Controller3d3b36cdb4f38dee299ffd8b2029aaa7 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller3d3b36cdb4f38dee299ffd8b2029aaa7.url(options),
    method: 'get',
})

Controller3d3b36cdb4f38dee299ffd8b2029aaa7.definition = {
    methods: ["get","head"],
    url: '/shifr',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/shifr'
 */
Controller3d3b36cdb4f38dee299ffd8b2029aaa7.url = (options?: RouteQueryOptions) => {
    return Controller3d3b36cdb4f38dee299ffd8b2029aaa7.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/shifr'
 */
Controller3d3b36cdb4f38dee299ffd8b2029aaa7.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller3d3b36cdb4f38dee299ffd8b2029aaa7.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/shifr'
 */
Controller3d3b36cdb4f38dee299ffd8b2029aaa7.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller3d3b36cdb4f38dee299ffd8b2029aaa7.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/shifr'
 */
    const Controller3d3b36cdb4f38dee299ffd8b2029aaa7Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controller3d3b36cdb4f38dee299ffd8b2029aaa7.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/shifr'
 */
        Controller3d3b36cdb4f38dee299ffd8b2029aaa7Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller3d3b36cdb4f38dee299ffd8b2029aaa7.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/shifr'
 */
        Controller3d3b36cdb4f38dee299ffd8b2029aaa7Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller3d3b36cdb4f38dee299ffd8b2029aaa7.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controller3d3b36cdb4f38dee299ffd8b2029aaa7.form = Controller3d3b36cdb4f38dee299ffd8b2029aaa7Form
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/youtube'
 */
const Controllereead2cc10890942e10f92884e5bdc477 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllereead2cc10890942e10f92884e5bdc477.url(options),
    method: 'get',
})

Controllereead2cc10890942e10f92884e5bdc477.definition = {
    methods: ["get","head"],
    url: '/youtube',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/youtube'
 */
Controllereead2cc10890942e10f92884e5bdc477.url = (options?: RouteQueryOptions) => {
    return Controllereead2cc10890942e10f92884e5bdc477.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/youtube'
 */
Controllereead2cc10890942e10f92884e5bdc477.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllereead2cc10890942e10f92884e5bdc477.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/youtube'
 */
Controllereead2cc10890942e10f92884e5bdc477.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllereead2cc10890942e10f92884e5bdc477.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/youtube'
 */
    const Controllereead2cc10890942e10f92884e5bdc477Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controllereead2cc10890942e10f92884e5bdc477.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/youtube'
 */
        Controllereead2cc10890942e10f92884e5bdc477Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllereead2cc10890942e10f92884e5bdc477.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/youtube'
 */
        Controllereead2cc10890942e10f92884e5bdc477Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllereead2cc10890942e10f92884e5bdc477.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controllereead2cc10890942e10f92884e5bdc477.form = Controllereead2cc10890942e10f92884e5bdc477Form
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
    '/telegram': Controllerd5ecae56bdcfbb8acbee4b606463b6bd,
    '/site-intel': Controller8afb288f411615c9a5737c884d0340a0,
    '/news-media-intel': Controller36a2b357ae6134bc8055dfa5be128cd4,
    '/shifr': Controller3d3b36cdb4f38dee299ffd8b2029aaa7,
    '/youtube': Controllereead2cc10890942e10f92884e5bdc477,
    '/settings/appearance': Controllere19ee86e9cf603ce1a59a1ec5d21dec5,
}

export default Controller