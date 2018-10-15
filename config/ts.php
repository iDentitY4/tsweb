<?php

return [

    'default' => env('TS_ADAPTER', 'serverquery'),

    'connections' => [
        'serverquery' => [
            'driver' => 'serverquery',
            'host' => 'sksystems.de',
            'port' => '10011',
            'server_port' => '9987',
            'username' => 'serveradmin',
            'password' => 'AwxRnhwR'
        ]
    ]

];
