<?php

return [
    'api' => [
        'title' => 'Securecy LMS API Documentation',
    ],

    'routes' => [
        /*
        * Route for accessing api documentation interface, e.g. api/documentation
        */
        'api' => 'api/docs',

        /*
        * Route for accessing parsed swagger yaml, e.g. api/documentation/swagger.yaml
        */
        'docs' => 'api/docs.json',
    ],

    'paths' => [
        /*
        * Absolute path to location where parsed swagger files will be stored
        */
        'docs' => storage_path('api-docs'),

        /*
        * Absolute path to your swagger annotation files base folder
        */
        'annotations' => base_path('storage/api-docs'),
    ],

    'scanOptions' => [
        /**
         * analyser: defaults to StaticAnalyser
         *
         * note: If you would like to use the StreamAnalyser, that comes with
         * swagger-php v4.0.0 +
         */
        'analyser' => \OpenApi\Analysers\StaticAnalyser::class,

        /**
         * alias options in the paths array to be scanned
         */
        'alias' => [],

        /**
         * Absolute path to directory that will be scanned for annotations
         */
        'paths' => [
            base_path('app'),
            base_path('storage/api-docs'),
        ],

        /**
         * Pattern of files tha will be scanned, defaults to .php
         */
        'pattern' => null,

        /**
         * Exclude path from scanning
         */
        'exclude' => [],

        /**
         * Custom OpenAPI spec processor.
         */
        'processor' => null,

        /**
         * Enable caching of the generated specification.
         */
        'cache' => [
            'etag' => env('L5_SWAGGER_USE_CACHE', true),
        ],
    ],

    /*
    * API security definitions
    */
    'securityDefinitions' => [
        'api_key' => [
            'type'        => 'apiKey',
            'description' => 'API key authentication',
            'name'        => 'api_key',
            'in'          => 'header',
        ],
    ],

    /*
    * Set this to `true` in production note: if you do this, you will have to generate swagger json in the build process
    */
    'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),

    /*
    * Set this to `true` if you would like to see the swagger-ui try it out feature
    */
    'try_it_out_enabled' => env('L5_SWAGGER_TRY_IT_OUT', true),

    /*
    * Servers defined in the OpenAPI spec
    */
    'servers' => [
        [
            'url' => env('APP_URL', 'http://localhost:8000') . '/api/v1',
            'description' => 'API Server',
        ],
    ],

    /*
    * basename of the swagger json file within the docs path `default is api-docs`
    */
    'doc_expansion' => env('L5_SWAGGER_DOC_EXPANSION', 'none'),

    /*
    * Deep linking swagger ui for easier navigation `default is false`
    */
    'deep_linking' => env('L5_SWAGGER_DEEP_LINKING', true),

    /*
    * If set to True, the swagger UI will be available directly at /api/docs.
    * If set to False, then it will only be available at /api/documentation with /api/docs.json
    */
    'ui' => [
        'display' => [
            'dark_mode' => env('L5_SWAGGER_UI_DARK_MODE', false),
        ],
    ],

    /*
    * Controls whether SwaggerUI loads the configurations at the startup of the page and displays the UI
    */
    'constants' => [
        'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://localhost:8000/api/v1'),
    ],

    /*
    * Uncomment all the parameters below to tweak the example requests in swagger
    */
    'operationFilter' => [],

    'parameterFilter' => [],

    'requestBodyFilter' => [],

    'responseFilter' => [],

    'modelFilter' => [],

    'securityFilter' => [],

    /*
    * Swagger UIBundle config options
    */
    'ui_config_memory' => false,

    'persist_authorization' => env('L5_SWAGGER_PERSIST_AUTHORIZATION', false),

    'operations' => [
        'summary_max_length' => null,
    ],

    /*
    * Pass the additional config key and value pair in the configs array to
    * generate documentation of your custom API.
    */
    'additional_config' => [
        'info' => [
            'x-logo' => [
                'url' => env('L5_SWAGGER_LOGO_URL', ''),
            ],
        ],
    ],
];
