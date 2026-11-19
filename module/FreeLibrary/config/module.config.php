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
    'controllers' => [
        'factories' => [
            \FreeLibrary\Controller\AuthController::class => \VuFind\Controller\AbstractBaseFactory::class,
        ],
        'aliases' => [
            'Auth' => \FreeLibrary\Controller\AuthController::class,
            'auth' => \FreeLibrary\Controller\AuthController::class,
        ],
    ],
    'router' => [
        'routes' => [
            'flpauth-pintest' => [
                'type' => \Laminas\Router\Http\Segment::class,
                'options' => [
                    'route' => '/auth/:type/:id/:pin/pintest',
                    'constraints' => [
                        'type' => 'linkedin|comics',
                    ],
                    'defaults' => [
                        'controller' => \FreeLibrary\Controller\AuthController::class,
                        'action' => 'pintest',
                    ],
                ],
            ],
            'flpauth-dump' => [
                'type' => \Laminas\Router\Http\Segment::class,
                'options' => [
                    'route' => '/auth/:type/:id[/:pin]/dump',
                    'constraints' => [
                        'type' => 'linkedin|comics',
                    ],
                    'defaults' => [
                        'controller' => \FreeLibrary\Controller\AuthController::class,
                        'action' => 'dump',
                    ],
                ],
            ],
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'auth/linkedin/pintest' => __DIR__ . '/../view/auth/linkedin/pintest.phtml',
            'auth/linkedin/dump'    => __DIR__ . '/../view/auth/linkedin/dump.phtml',
            'auth/comics/pintest'   => __DIR__ . '/../view/auth/comics/pintest.phtml',
            'auth/comics/dump'      => __DIR__ . '/../view/auth/comics/dump.phtml',
        ],
    ],
    'service_manager' => [
        'factories' => [
            \FreeLibrary\ILS\Logic\Holdability::class => \FreeLibrary\ILS\Logic\HoldabilityFactory::class,
        ],
    ],
];

return $config;
