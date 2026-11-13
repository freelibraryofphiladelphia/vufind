<?php

namespace Flp\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'ils_driver' => [
                'factories' => [
                    \Flp\ILS\Driver\Symphony::class => \VuFind\ILS\Driver\SymphonyFactory::class,
                ],
                'aliases' => [
                    'symphony' => \Flp\ILS\Driver\Symphony::class,
                    \VuFind\ILS\Driver\Symphony::class => \Flp\ILS\Driver\Symphony::class,
                ],
            ],
        ],
    ],
];

return $config;
