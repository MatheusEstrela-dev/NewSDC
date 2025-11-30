<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | These options configures if and how Inertia uses Server Side Rendering
    | to pre-render the initial visits made to your application's pages.
    |
    | Do note that enabling these options will NOT automatically make SSR work,
    | as a separate rendering service needs to be available. To learn more,
    | please visit https://inertiajs.com/server-side-rendering
    |
    */

    'ssr' => [

        'enabled' => env('INERTIA_SSR_ENABLED', true),

        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | The values described here are used to configure Inertia's testing
    | behavior. They are used by the Inertia testing assertions.
    |
    */

    'testing' => [

        'ensure_pages_exist' => true,

        'page_paths' => [
            resource_path('js/Pages'),
        ],

        'page_extensions' => [
            'js',
            'jsx',
            'ts',
            'tsx',
            'vue',
            'svelte',
        ],

    ],

];
