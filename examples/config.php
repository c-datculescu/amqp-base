<?php

return [
    'connections' => [
        'main' => [
            'host'     => '192.168.56.52',
            'port'     => 5672,
            'login'    => 'admin',
            'password' => 'mort487',
            'vhost'    => '/'
        ],
    ],
    'exchanges'   => [
        'global' => [
            'name'       => 'global',
            'connection' => 'main',
            'flags'      => ['durable'],
            'type'       => 'topic'
        ]
    ],
    'queues' => [
        'debug' => [
            'name' => 'debug',
            'flags' => ['durable'],
            'connection' => 'main',
            'bindings' => [
                ['exchange' => 'global', 'routing_key' => '#'],
                ['exchange' => 'global', 'routing_key' => '']
            ]
        ]
    ]
];