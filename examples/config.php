<?php

return [
    'connections' => [
        'main' => [
            'host'     => '192.168.56.52',
            'port'     => 5672,
            'login'    => 'admin',
            'password' => 'admin',
            'vhost'    => '/',
            'prefetch_count' => 10,
            'publisher_confirms' => true,
        ],
    ],
    'exchanges'   => [
        'alt' => [
            'name'       => 'alternate',
            'connection' => 'main',
            'flags'      => ['durable'],
            'type'       => 'topic',
        ],
        'global' => [
            'name'       => 'global',
            'connection' => 'main',
            'flags'      => ['durable'],
            'type'       => 'topic',
            'arguments'  => ['alternate-exchange' => 'alt'],
        ]
    ],
    'queues' => [
        'debug' => [
            'name' => 'debug',
            'flags' => ['durable'],
            'connection' => 'main',
            'bindings' => [
                ['exchange' => 'global', 'routing_key' => '#'],
                ['exchange' => 'global', 'routing_key' => ''],
            ]
        ]
    ]
];