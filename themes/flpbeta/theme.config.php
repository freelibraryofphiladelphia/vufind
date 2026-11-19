<?php
return [
    'extends' => 'bootstrap5',
    'sass' => [
        'custom.scss',
        'footer.scss',
    ],
    'css' => [
        'brands.css',
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
    // Override preset icons here
    'icons' => [
        'sets' => [
            /**
             * Define icon sets here.
             *
             * All sets need:
             * - 'template': which template the icon renders with
             * - 'src': the location of the relevant resource (font, css, images)
             * - 'prefix': prefix to place before each icon name for convenience
             *             (ie. fa- for FontAwesome, default "")
             * If you want to add various styles, make sure they are first imported through the custom.scss
             * Then just add ,'fa-thin' after the the token name of the icon within the template. See the footer for examples
             */
            'FontAwesome' => [
                // Font Awesome Pro 7
                'template' => 'font',
                'prefix' => 'fa fa-',
                'src' => 'custom.scss',
            ],
            'FontAwesomeBrands' => [
                // Font Awesome Pro 7
                'template' => 'font',
                'prefix' => 'fa-brands fa-',
                'src' => 'custom.scss',
            ],
        ],
        'aliases' => [
            /**
             * Icons can be assigned or overridden here
             *
             * Format: 'icon' => [set:]icon[:extra_classes]
             * Icons assigned without set will use the defaultSet.
             * In order to specify extra CSS classes, you must also specify a set.
             *
             * All of the items below have been specified with FontAwesome to allow
             * for a strong inheritance safety net but this is not required.
             */
            // to override an icon, go to the bootstrap5 theme and select the icon you would like to change and add here
            'social-facebook' => 'FontAwesomeBrands:facebook-square',
            'social-instagram' => 'FontAwesomeBrands:instagram',
            'social-youtube' => 'FontAwesomeBrands:youtube',
            'social-linkedin' => 'FontAwesomeBrands:linkedin',
            'test-cat' => 'FontAwesome:cat',

            // Begin Bootstrap5 Default Icon Overrides
            'my-account' => 'FontAwesome:circle-user fa-regular',
            'my-account-notification' => 'Alias:notification',
            'notification' => 'FontAwesome:bell fa-solid fa-shake fa-xl fa-a11y-animation',
            'place-hold' => 'FontAwesome:flag-swallowtail fa-sharp-duotone',
            'sign-in' => 'FontAwesome:right-to-bracket',
            'sign-out' => 'FontAwesome:right-from-bracket',
            'spinner' => 'FontAwesome:spinner-scale fa-solid fa-spin-pulse',
            'user-favorites' => 'FontAwesome:hearts fa-sharp-duotone',
            'user-holds' => 'FontAwesome:flag-swallowtail fa-sharp-duotone',


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
