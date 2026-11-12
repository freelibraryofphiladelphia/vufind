<?php
return [
    'extends' => 'bootstrap5',
    'sass' => [
        'custom.scss',
        'footer.scss',
    ],
    'js' => [
        /**
         * Entries in this section can either be specified as array or as string.
         * Array format is preferred.
         *
         * Available array options:
         * - file: the path to the file
         * - load_after: Use this to explicitly load the file after the given other
         *   file. This may NOT be used together with a priority setting.
         * - priority: an optional priority (lower value means higher priority).
         *      Default convention for VuFind's own themes:
         *          - 1xx => vendor (third-party code)
         *          - 2xx => VuFind library (general-purpose code)
         *          - 3xx => VuFind scripts (highly VuFind-specific code)
         * - position: 'header' (default) or 'footer'
         * - disabled: if set to true in a child theme, the matching file will be
         *   removed if it was included by a parent theme.
         *
         * Entries with neither priority nor load_after will be loaded after all
         * other entries.
         *
         * Strings are supported for legacy backwards compatibility reasons. example:
         * - 'example.js' => same as ['file' => 'example.js']
         */
    ],
    'favicon' => [
        [
            'href' => 'favicons/android-chrome-192x192.png',
            'rel' => 'icon',
            'type' => 'image/png',
            'sizes' => '192x192',
        ],
        [
            'href' => 'favicons/android-chrome-512x512.png',
            'rel' => 'icon',
            'type' => 'image/png',
            'sizes' => '512x512',
        ],
        [
            'href' => 'favicons/apple-touch-icon.png',
            'rel' => 'apple-touch-icon',
            'type' => 'image/png',
            'sizes' => '180x180',
        ],
        [
            'href' => 'favicons/favicon-16x16.png',
            'rel' => 'icon',
            'type' => 'image/png',
            'sizes' => '16x16',
        ],
        [
            'href' => 'favicons/favicon-32x32.png',
            'rel' => 'icon',
            'type' => 'image/png',
            'sizes' => '32x32',
        ],
        [
            'href' => 'favicons/favicon.ico',
            'rel' => 'icon',
            'type' => 'image/x-icon',
            'sizes' => '48x48',
        ],
    ],
    /**
     * The following is all custom attributes for this theme
     * in order to demonstrate the themeConfig view helper.
     */
    'home-content' => [
        'links' => [
            'Setup' => 'install-home',
            'Dev Tools' => 'devtools-home',
        ],
    ],

    'header-nav' => parse_ini_file(__DIR__ . '/templates/header-nav.ini', true),
];
