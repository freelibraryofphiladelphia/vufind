<?php
return [
    'extends' => 'bootstrap5',

    'css' => [
        'custom.css',
    ],
    'sass' => [
        'custom.scss',
        'footer.scss',
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

    'header-nav' => parse_ini_file(__DIR__ . '/header-nav.ini', true),
];