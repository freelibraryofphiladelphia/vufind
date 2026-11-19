<?php

namespace FreeLibrary\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'ils_driver' => [
                'factories' => [
                    \FreeLibrary\ILS\Driver\Symphony::class => \FreeLibrary\ILS\Driver\SymphonyFactory::class,
                ],
                'aliases' => [
                    'symphony' => \FreeLibrary\ILS\Driver\Symphony::class,
                    \VuFind\ILS\Driver\Symphony::class => \FreeLibrary\ILS\Driver\Symphony::class,
                ],
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            \FreeLibrary\ILS\Logic\Holdability::class => \FreeLibrary\ILS\Logic\HoldabilityFactory::class,
        ],
    ],
];

return $config;
