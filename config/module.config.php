<?php

namespace ImportIt;

return [
    'api_adapters' => [
        'invokables' => [
            'importit_logs' => Api\Adapter\LogAdapter::class,
            'importit_sources' => Api\Adapter\SourceAdapter::class,
            'importit_source_records' => Api\Adapter\SourceRecordAdapter::class,
        ],
    ],
    'browse_defaults' => [
        'admin' => [
            'importit_logs' => [
                'sort_by' => 'timestamp',
                'sort_order' => 'desc',
            ],
            'importit_sources' => [
                'sort_by' => 'name',
                'sort_order' => 'asc',
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'ImportIt\Controller\Admin\Job' => Controller\Admin\JobController::class,
            'ImportIt\Controller\Admin\Log' => Controller\Admin\LogController::class,
        ],
        'factories' => [
            'ImportIt\Controller\Admin\Source' => Service\Controller\Admin\SourceControllerFactory::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\SourceImportForm::class => Form\SourceImportForm::class,
        ],
        'factories' => [
            Form\SourceAddForm::class => Service\Form\SourceAddFormFactory::class,
            Form\SourceEditForm::class => Service\Form\SourceEditFormFactory::class,
        ],
    ],
    'importit_logger' => [
        'dir' => OMEKA_PATH . '/logs/importit',
    ],
    'importit_source_types' => [
        'factories' => [
            'server_side_mets' => Service\SourceType\ServerSideMetsFactory::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Import It', // @translate
                'route' => 'admin/importit/source',
                'resource' => 'ImportIt\Controller\Admin\Source',
                'privilege' => 'browse',
                'class' => 'importit',
                'pages' => [
                    [
                        'route' => 'admin/importit/source-id',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/importit/job',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/importit/job-id',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/importit/log',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/importit/log-id',
                        'visible' => false,
                    ],
                ],
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'importit' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/importit',
                            'defaults' => [
                                '__NAMESPACE__' => 'ImportIt\Controller\Admin',
                                'controller' => 'Index',
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'source' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/source[/:action]',
                                    'defaults' => [
                                        'controller' => 'source',
                                        'action' => 'browse',
                                    ],
                                ],
                            ],
                            'source-id' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/source/:id[/:action]',
                                    'constraints' => [
                                        'id' => '\d+',
                                    ],
                                    'defaults' => [
                                        'controller' => 'source',
                                        'action' => 'show',
                                    ],
                                ],
                            ],
                            'job' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/source/:source-id/job',
                                    'constraints' => [
                                        'source-id' => '\d+',
                                    ],
                                    'defaults' => [
                                        'controller' => 'job',
                                        'action' => 'browse',
                                    ],
                                ],
                            ],
                            'job-id' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/source/:source-id/job/:job-id[/:action]',
                                    'constraints' => [
                                        'source-id' => '\d+',
                                        'job-id' => '\d+',
                                    ],
                                    'defaults' => [
                                        'controller' => 'job',
                                        'action' => 'show',
                                    ],
                                ],
                            ],
                            'log' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/source/:source-id/job/:job-id/log',
                                    'constraints' => [
                                        'source-id' => '\d+',
                                        'job-id' => '\d+',
                                    ],
                                    'defaults' => [
                                        'controller' => 'log',
                                        'action' => 'browse',
                                    ],
                                ],
                            ],
                            'log-download' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/source/:source-id/job/:job-id/log.:format',
                                    'constraints' => [
                                        'source-id' => '\d+',
                                        'job-id' => '\d+',
                                        'format' => 'jsonl|txt',
                                    ],
                                    'defaults' => [
                                        'controller' => 'log',
                                        'action' => 'download',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'ImportIt\SourceTypeManager' => Service\SourceType\SourceTypeManagerFactory::class,
            'ImportIt\Logger' => Service\LoggerFactory::class,
            'ImportIt\Job\Dispatcher' => Service\Job\DispatcherFactory::class,
        ],
    ],
    'sort_defaults' => [
        'admin' => [
            'importit_sources' => [
                'name' => 'Name', // @translate
            ],
            'importit_logs' => [
                'timestamp' => 'Timestamp', // @translate
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
];
