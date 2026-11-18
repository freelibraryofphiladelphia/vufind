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
            'barcode' => 'FontAwesome:barcode',
            'browzine-best' => 'FontAwesome:file-lines',
            'browzine-concern' => 'FontAwesome:exclamation',
            'browzine-delivery' => 'FontAwesome:paper-plane',
            'browzine-issue' => 'Alias:format-serial',
            'browzine-link-resolver' => 'FontAwesome:file',
            'browzine-pdf' => 'FontAwesome:file-pdf fa-regular',
            'browzine-retraction' => 'FontAwesome:exclamation',
            'browzine-view' => 'FontAwesome:file',
            'cart' => 'FontAwesome:suitcase',
            'cart-add' => 'FontAwesome:plus',
            'cart-empty' => 'FontAwesome:times',
            'cart-remove' => 'FontAwesome:minus-circle',
            'cite' => 'FontAwesome:asterisk',
            'cites' => 'Unicode:275D',
            'cited-by' => 'Unicode:275E',
            'clone' => 'FontAwesome:clone',
            'collapse' => 'Collapse:_', // uses the icons below
            'collapse-close' => 'FontAwesome:chevron-up',
            'collapse-open' => 'FontAwesome:chevron-down',
            'user-content' => 'FontAwesome:comment',
            'cover-replacement' => 'FontAwesome:question',
            'currency-eur' => 'FontAwesome:euro-sign',
            'currency-gbp' => 'FontAwesome:sterling-sign',
            'currency-inr' => 'FontAwesome:indian-rupee-sign',
            'currency-jpy' => 'FontAwesome:yen-sign',
            'currency-krw' => 'FontAwesome:won-sign',
            'currency-rmb' => 'FontAwesome:yen-sign',
            'currency-rub' => 'FontAwesome:ruble-sign',
            'currency-try' => 'FontAwesome:turkish-lira-sign',
            'currency-usd' => 'FontAwesome:dollar-sign',
            'currency-won' => 'FontAwesome:won-sign',
            'currency-yen' => 'FontAwesome:yen-sign',
            'dropdown-caret' => 'Alias:dropdown-open', // deprecated, use dropdown-open instead
            'dropdown-close' => 'FontAwesome:caret-up',
            'dropdown-open' => 'FontAwesome:caret-down',
            'explain' => 'FontAwesome:question-circle',
            'export' => 'FontAwesome:up-right-from-square',
            'external-link' => 'FontAwesome:link',
            'facet-applied' => 'FontAwesome:check',
            'facet-checked' => 'FontAwesome:check-square fa-regular',
            'facet-collapse' => 'FontAwesome:caret-down',
            'facet-exclude' => 'FontAwesome:times',
            'facet-expand' => 'FontAwesome:caret-right',
            'facet-noncollapsible' => 'FontAwesome:none',
            'facet-unchecked' => 'FontAwesome:square fa-regular',
            'feedback' => 'FontAwesome:envelope',
            'format-atlas' => 'FontAwesome:compass',
            'format-book' => 'FontAwesome:book',
            'format-braille' => 'FontAwesome:hand-point-up fa-regular',
            'format-cdrom' => 'FontAwesome:laptop',
            'format-chart' => 'FontAwesome:signal',
            'format-chipcartridge' => 'FontAwesome:laptop',
            'format-collage' => 'FontAwesome:image',
            'format-default' => 'FontAwesome:book',
            'format-disccartridge' => 'FontAwesome:laptop',
            'format-drawing' => 'FontAwesome:image',
            'format-ebook' => 'FontAwesome:file-lines fa-regular',
            'format-electronic' => 'FontAwesome:file-zipper fa-regular',
            'format-file' => 'FontAwesome:file fa-regular',
            'format-filmstrip' => 'FontAwesome:film',
            'format-flashcard' => 'FontAwesome:bolt',
            'format-floppydisk' => 'FontAwesome:floppy-disk fa-regular',
            'format-folder' => 'FontAwesome:folder',
            'format-globe' => 'FontAwesome:earth-americas',
            'format-journal' => 'FontAwesome:file-lines fa-regular',
            'format-kit' => 'FontAwesome:briefcase',
            'format-manuscript' => 'FontAwesome:file-lines fa-regular',
            'format-map' => 'FontAwesome:compass',
            'format-microfilm' => 'FontAwesome:film',
            'format-motionpicture' => 'FontAwesome:video',
            'format-musicalscore' => 'FontAwesome:music',
            'format-musicrecording' => 'FontAwesome:music',
            'format-newspaper' => 'FontAwesome:file-lines fa-regular',
            'format-online' => 'FontAwesome:laptop',
            'format-painting' => 'FontAwesome:image',
            'format-pdf' => 'FontAwesome:file-pdf',
            'format-photo' => 'FontAwesome:image',
            'format-photonegative' => 'FontAwesome:image',
            'format-physicalobject' => 'FontAwesome:archive',
            'format-print' => 'FontAwesome:image',
            'format-sensorimage' => 'FontAwesome:image',
            'format-serial' => 'FontAwesome:file-lines fa-regular',
            'format-slide' => 'FontAwesome:film',
            'format-software' => 'FontAwesome:laptop',
            'format-soundcassette' => 'FontAwesome:headphones',
            'format-sounddisc' => 'FontAwesome:laptop',
            'format-soundrecording' => 'FontAwesome:headphones',
            'format-tapecartridge' => 'FontAwesome:laptop',
            'format-tapecassette' => 'FontAwesome:headphones',
            'format-tapereel' => 'FontAwesome:film',
            'format-transparency' => 'FontAwesome:film',
            'format-unknown' => 'FontAwesome:question',
            'format-video' => 'FontAwesome:video',
            'format-videocartridge' => 'FontAwesome:video',
            'format-videocassette' => 'FontAwesome:video',
            'format-videodisc' => 'FontAwesome:laptop',
            'format-videoreel' => 'FontAwesome:video',
            'hierarchy-collapse' => 'Alias:facet-collapse',
            'hierarchy-collection' => 'FontAwesome:folder-open fa-regular',
            'hierarchy-expand' => 'Alias:facet-expand',
            'hierarchy-noncollapsible' => 'Alias:facet-noncollapsible',
            'hierarchy-record' => 'FontAwesome:file fa-regular',
            'hierarchy-tree' => 'FontAwesome:sitemap',
            'keyboard' => 'FontAwesome:keyboard fa-regular',
            'lightbox-close' => 'FontAwesome:times',
            'link' => 'FontAwesome:link',
            'more' => 'FontAwesome:chevron-circle-right',
            'more-rtl' => 'FontAwesome:chevron-circle-left',
            'my-account' => 'FontAwesome:circle-user fa-regular',
            'my-account-notification' => 'Alias:notification',
            'my-account-warning' => 'Alias:warning',
            'notification' => 'FontAwesome:circle-user-circle-exclamation fa-regular fa-xl',
            'offcanvas-hide-left' => 'FontAwesome:angle-right',
            'offcanvas-hide-right' => 'FontAwesome:angle-left',
            'offcanvas-show-left' => 'FontAwesome:angle-double-left',
            'offcanvas-show-right' => 'FontAwesome:angle-double-right',
            'options' => 'FontAwesome:gear',
            'overdrive' => 'FontAwesome:download',
            'overdrive-cancel-hold' => 'Alias:ui-cancel',
            'overdrive-checkout' => 'FontAwesome:arrow-left',
            'overdrive-checkout-rtl' => 'FontAwesome:arrow-right',
            'overdrive-download' => 'FontAwesome:download',
            'overdrive-edit-hold' => 'Alias:ui-edit',
            'overdrive-edit-hold-suspension' => 'FontAwesome:calendar-days',
            'overdrive-help' => 'FontAwesome:question-circle',
            'overdrive-place-hold' => 'Alias:place-hold',
            'overdrive-return' => 'FontAwesome:undo',
            'overdrive-sign-in' => 'FontAwesome:right-to-bracket',
            'overdrive-success' => 'FontAwesome:check',
            'overdrive-suspend-hold' => 'Alias:place-hold',
            'overdrive-warning' => 'Alias:warning',
            'page-first' => 'FontAwesome:angle-double-left',
            'page-first-rtl' => 'FontAwesome:angle-double-right',
            'page-last' => 'FontAwesome:angle-double-right',
            'page-last-rtl' => 'FontAwesome:angle-double-left',
            'page-next' => 'FontAwesome:angle-right',
            'page-next-rtl' => 'FontAwesome:angle-left',
            'page-prev' => 'FontAwesome:angle-left',
            'page-prev-rtl' => 'FontAwesome:angle-right',
            'place-hold' => 'FontAwesome:receipt',
            'place-ill-request' => 'FontAwesome:right-left',
            'place-recall' => 'FontAwesome:receipt',
            'place-storage-retrieval' => 'FontAwesome:truck',
            'print' => 'FontAwesome:print',
            'profile' => 'FontAwesome:user',
            'profile-card-delete' => 'Alias:ui-delete',
            'profile-card-edit' => 'Alias:ui-edit',
            'profile-change-password' => 'FontAwesome:key',
            'profile-delete' => 'Alias:ui-delete',
            'profile-edit' => 'Alias:ui-edit',
            'profile-email' => 'FontAwesome:envelope',
            'profile-sms' => 'FontAwesome:mobile-screen-button',
            'qrcode' => 'FontAwesome:qrcode',
            'rating-half' => 'FontAwesome:star-half',
            'rating-full' => 'FontAwesome:star',
            'search' => 'FontAwesome:search',
            'search-delete' => 'Alias:ui-delete',
            'search-filter-remove' => 'FontAwesome:times',
            'search-rss' => 'FontAwesome:rss',
            'search-save' => 'Alias:ui-save',
            'search-schedule-alert' => 'FontAwesome:exclamation-circle',
            'send-email' => 'FontAwesome:envelope',
            'send-sms' => 'FontAwesome:mobile-screen-button',
            'sign-in' => 'FontAwesome:right-to-bracket',
            'sign-out' => 'FontAwesome:right-from-bracket',
            'spinner' => 'FontAwesome:spinner:icon--spin',
            'status-available' => 'FontAwesome:check',
            'status-pending' => 'FontAwesome:clock fa-regular',
            'status-ready' => 'FontAwesome:bell',
            'status-unavailable' => 'FontAwesome:times',
            'status-uncertain' => 'FontAwesome:circle',
            'status-unknown' => 'FontAwesome:circle',
            'tag-add' => 'Alias:ui-add',
            'tag-remove' => 'Alias:ui-remove',
            'tree-context' => 'FontAwesome:sitemap',
            'truncate-less' => 'FontAwesome:arrow-up',
            'truncate-more' => 'FontAwesome:arrow-down',
            'ui-add' => 'FontAwesome:plus-circle',
            'ui-cancel' => 'FontAwesome:ban',
            'ui-close' => 'FontAwesome:times',
            'ui-delete' => 'FontAwesome:trash-can fa-regular',
            'ui-dots-menu' => 'FontAwesome:ellipsis-h',
            'ui-edit' => 'FontAwesome:pen-to-square fa-regular',
            'ui-failure' => 'FontAwesome:times',
            'ui-menu' => 'FontAwesome:bars',
            'ui-remove' => 'FontAwesome:times',
            'ui-reset-search' => 'Alias:ui-remove',
            'ui-save' => 'FontAwesome:floppy-disk fa-regular',
            'ui-success' => 'FontAwesome:check',
            'user-checked-out' => 'FontAwesome:book',
            'user-favorites' => 'FontAwesome:hearts fa-sharp-duotone',
            'user-holds' => 'FontAwesome:flag',
            'user-ill-requests' => 'FontAwesome:right-left',
            'user-list' => 'FontAwesome:list',
            'user-list-add' => 'FontAwesome:bookmark fa-regular',
            'user-list-delete' => 'Alias:ui-delete',
            'user-list-edit' => 'Alias:ui-edit',
            'user-list-entry-edit' => 'Alias:ui-edit',
            'user-list-remove' => 'Alias:ui-remove',
            'user-loan-history' => 'FontAwesome:history',
            'user-public-list-indicator' => 'FontAwesome:earth-americas',
            'user-storage-retrievals' => 'FontAwesome:archive',
            'view-grid' => 'FontAwesome:th',
            'view-list' => 'FontAwesome:list',
            'view-visual' => 'FontAwesome:th-large',
            'warning' => 'FontAwesome:exclamation-triangle',

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
